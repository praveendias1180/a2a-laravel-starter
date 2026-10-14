<?php

declare(strict_types=1);

namespace App\A2A;

use A2A\Helpers\ProtoHelpers;
use A2A\Server\AgentExecution\AgentExecutor;
use A2A\Server\AgentExecution\RequestContext;
use A2A\Server\Events\EventQueue;
use A2A\Server\Tasks\TaskUpdater;
use A2A\Types\Part;
use A2A\Types\Role;

/**
 * The A2A side of the agent: turns a message into task events.
 *
 * - "convert 10 km to miles" -> WORKING, an artifact "10 km = 6.2137 mi", COMPLETED
 * - "convert 10 km"          -> INPUT_REQUIRED ("To which unit?"); answer "miles"
 *                               on the same task and it completes
 * - anything it can't read   -> COMPLETED with a short help text
 */
final class UnitConverterExecutor implements AgentExecutor
{
    public function __construct(private readonly UnitConverter $converter) {}

    public function execute(RequestContext $context, EventQueue $eventQueue): void
    {
        $message = $context->message();
        if ($context->currentTask() === null && $message !== null) {
            $eventQueue->enqueueEvent(ProtoHelpers::newTaskFromUserMessage($message));
        }
        $updater = new TaskUpdater($eventQueue, (string) $context->taskId(), (string) $context->contextId());
        $say = static fn (string $text) => $updater->newAgentMessage([new Part(['text' => $text])]);

        $request = $this->converter->parse($this->conversation($context));
        if ($request === null) {
            $updater->addArtifact([new Part(['text' => $this->help()])], name: 'help', lastChunk: true);
            $updater->complete();

            return;
        }

        ['amount' => $amount, 'from' => $from, 'to' => $to] = $request;
        // A follow-up on a paused task is just the missing unit ("miles").
        $to ??= $this->converter->unit($context->getUserInput());
        if ($to === null) {
            $options = implode(', ', $this->converter->compatibleUnits($from));
            $updater->requiresInput($say("To which unit? For example: {$options}."));

            return;
        }

        $updater->startWork($say("Converting {$amount} {$from} to {$to}..."));
        try {
            $answer = $this->converter->describe($amount, $from, $to);
        } catch (\InvalidArgumentException $e) {
            $updater->failed($say($e->getMessage()));

            return;
        }
        $updater->addArtifact([new Part(['text' => $answer])], name: 'conversion', lastChunk: true);
        $updater->complete();
    }

    public function cancel(RequestContext $context, EventQueue $eventQueue): void
    {
        (new TaskUpdater($eventQueue, (string) $context->taskId(), (string) $context->contextId()))->cancel();
    }

    /**
     * Everything the user has said on this task, oldest first, so that
     * "convert 10 km" + "miles" reads as one request.
     */
    private function conversation(RequestContext $context): string
    {
        $said = [];
        foreach ($context->currentTask()?->getHistory() ?? [] as $earlier) {
            if ($earlier->getRole() === Role::ROLE_USER && $earlier->getMessageId() !== $context->message()?->getMessageId()) {
                foreach ($earlier->getParts() as $part) {
                    $said[] = $part->getText();
                }
            }
        }
        $said[] = $context->getUserInput();

        return implode(' ', array_filter($said, static fn (string $text): bool => $text !== ''));
    }

    private function help(): string
    {
        return "I convert units. Try: \"convert 10 km to miles\", \"72 f in c\" or \"2 cups to ml\".\n"
            .'I know length (mm, cm, m, km, in, ft, yd, mi), mass (g, kg, oz, lb), '
            .'volume (ml, l, cup, gal) and temperature (c, f, k).';
    }
}
