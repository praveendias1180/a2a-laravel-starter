<?php

use App\A2A\UnitConverterAgentCard;
use App\A2A\UnitConverterExecutor;
use Illuminate\Support\Facades\Route;

/*
| Your A2A agent. This one line mounts:
|   GET  /.well-known/agent-card.json   the Agent Card (how other agents find you)
|   POST /a2a/jsonrpc                   the JSON-RPC binding
|   *    /a2a/rest/...                  the HTTP+JSON binding
| Add ->middleware('auth:sanctum') (or any auth) to protect it; the card stays public.
| Guide: https://praveendias1180.github.io/a2a-php/guides/laravel/
*/
Route::a2a('/a2a', agentCard: UnitConverterAgentCard::class, executor: UnitConverterExecutor::class);
