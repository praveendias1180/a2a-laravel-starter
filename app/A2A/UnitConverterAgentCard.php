<?php

declare(strict_types=1);

namespace App\A2A;

use A2A\Laravel\Contracts\AgentCardProvider;
use A2A\Types\AgentCapabilities;
use A2A\Types\AgentCard;
use A2A\Types\AgentSkill;

/**
 * What other agents see at /.well-known/agent-card.json. There are no
 * supported_interfaces here: Route::a2a() fills them in from your routes and
 * APP_URL.
 */
final class UnitConverterAgentCard implements AgentCardProvider
{
    public function agentCard(): AgentCard
    {
        return new AgentCard([
            'name' => 'Unit Converter',
            'description' => 'Converts length, mass, volume and temperature. A starter A2A agent built with Laravel.',
            'version' => '1.0.0',
            'capabilities' => new AgentCapabilities(['streaming' => true]),
            'default_input_modes' => ['text/plain'],
            'default_output_modes' => ['text/plain'],
            'skills' => [new AgentSkill([
                'id' => 'convert-units',
                'name' => 'Convert units',
                'description' => 'Converts an amount from one unit to another, and asks for the target unit if you leave it out.',
                'tags' => ['units', 'conversion', 'demo'],
                'examples' => ['convert 10 km to miles', '72 f in c', '2 cups to ml'],
            ])],
        ]);
    }
}
