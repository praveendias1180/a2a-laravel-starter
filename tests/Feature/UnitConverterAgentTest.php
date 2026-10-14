<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Talks to the agent the way another agent would: JSON-RPC and HTTP+JSON
 * requests with the A2A-Version header.
 */
class UnitConverterAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_agent_card_is_published(): void
    {
        $this->getJson('/.well-known/agent-card.json')
            ->assertOk()
            ->assertJsonPath('name', 'Unit Converter')
            ->assertJsonPath('capabilities.streaming', true)
            ->assertJsonPath('supportedInterfaces.0.protocolBinding', 'JSONRPC')
            ->assertJsonPath('skills.0.id', 'convert-units');
    }

    public function test_it_converts_over_json_rpc(): void
    {
        $this->rpc('SendMessage', ['message' => $this->message('convert 10 km to miles')])
            ->assertOk()
            ->assertJsonPath('result.task.status.state', 'TASK_STATE_COMPLETED')
            ->assertJsonPath('result.task.artifacts.0.parts.0.text', '10 km = 6.2137 mi');
    }

    public function test_it_converts_over_http_json(): void
    {
        $this->postJson('/a2a/rest/message:send', ['message' => $this->message('72 f in c')], ['A2A-Version' => '1.0'])
            ->assertOk()
            ->assertJsonPath('task.status.state', 'TASK_STATE_COMPLETED')
            ->assertJsonPath('task.artifacts.0.parts.0.text', '72 °F = 22.2222 °C');
    }

    public function test_it_asks_for_the_target_unit_and_continues_the_same_task(): void
    {
        $first = $this->rpc('SendMessage', ['message' => $this->message('convert 10 km')])
            ->assertJsonPath('result.task.status.state', 'TASK_STATE_INPUT_REQUIRED');
        $this->assertStringStartsWith('To which unit?', (string) $first->json('result.task.status.message.parts.0.text'));
        $taskId = (string) $first->json('result.task.id');

        $this->rpc('SendMessage', ['message' => $this->message('miles', $taskId)])
            ->assertJsonPath('result.task.id', $taskId)
            ->assertJsonPath('result.task.status.state', 'TASK_STATE_COMPLETED')
            ->assertJsonPath('result.task.artifacts.0.parts.0.text', '10 km = 6.2137 mi');
    }

    public function test_mixing_dimensions_fails_the_task(): void
    {
        $this->rpc('SendMessage', ['message' => $this->message('1 kg to km')])
            ->assertJsonPath('result.task.status.state', 'TASK_STATE_FAILED')
            ->assertJsonPath('result.task.status.message.parts.0.text', "Can't convert mass to length.");
    }

    public function test_anything_else_gets_help(): void
    {
        $response = $this->rpc('SendMessage', ['message' => $this->message('hello')])
            ->assertJsonPath('result.task.status.state', 'TASK_STATE_COMPLETED');

        $this->assertStringStartsWith('I convert units.', (string) $response->json('result.task.artifacts.0.parts.0.text'));
    }

    public function test_streaming_sends_each_event_as_it_happens(): void
    {
        $response = $this->rpc('SendStreamingMessage', ['message' => $this->message('5 kg to lb')]);
        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', (string) $response->headers->get('Content-Type'));

        $events = $this->sseEvents($response->streamedContent());
        $this->assertSame(
            ['task', 'statusUpdate', 'artifactUpdate', 'statusUpdate'],
            array_map(fn (array $event): string => (string) array_key_first($event['result']), $events),
        );
        $this->assertSame('5 kg = 11.0231 lb', $events[2]['result']['artifactUpdate']['artifact']['parts'][0]['text']);
        $this->assertSame('TASK_STATE_COMPLETED', $events[3]['result']['statusUpdate']['status']['state']);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function rpc(string $method, array $params): TestResponse
    {
        return $this->postJson('/a2a/jsonrpc', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], ['A2A-Version' => '1.0']);
    }

    /**
     * @return array<string, mixed>
     */
    private function message(string $text, ?string $taskId = null): array
    {
        return array_filter([
            'messageId' => bin2hex(random_bytes(8)),
            'role' => 'ROLE_USER',
            'parts' => [['text' => $text]],
            'taskId' => $taskId,
        ]);
    }

    /**
     * The JSON payloads of a Server-Sent Events body.
     *
     * @return list<array<string, mixed>>
     */
    private function sseEvents(string $body): array
    {
        preg_match_all('/^data:\s?(.*)$/m', $body, $matches);

        return array_map(fn (string $data): array => json_decode($data, true), $matches[1]);
    }
}
