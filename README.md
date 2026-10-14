# A2A Laravel Starter

A Laravel app that is already an [A2A (Agent2Agent)](https://a2a-protocol.org/latest/specification/) agent. Clone it, run it, then swap the example agent for your own.

It is built on [`praveendias1180/a2a-laravel`](https://github.com/praveendias1180/a2a-php), the Laravel bridge for the A2A PHP SDK. That SDK targets **A2A 1.0**, has the same shape as the official Python SDK, and passes the official A2A TCK.

## Run it in 2 minutes

```bash
composer create-project praveendias1180/a2a-laravel-starter my-agent
cd my-agent
php artisan serve
```

Or click **Use this template** on GitHub, clone your copy, and run `composer run setup`, then `php artisan serve`.

Your agent is now live at `http://localhost:8000`:

| URL | What |
|---|---|
| `/.well-known/agent-card.json` | the Agent Card, which tells other agents what yours can do |
| `/a2a/jsonrpc` | the JSON-RPC endpoint |
| `/a2a/rest/...` | the HTTP+JSON endpoint |

## Talk to it

With the official [A2A CLI](https://github.com/a2aproject/a2a-cli):

```bash
a2a send -a http://localhost:8000 --stream "convert 10 km to miles"
```

Or with curl:

```bash
curl -s http://localhost:8000/a2a/rest/message:send \
  -H 'Content-Type: application/json' -H 'A2A-Version: 1.0' \
  -d '{"message":{"messageId":"1","role":"ROLE_USER","parts":[{"text":"72 f in c"}]}}'
```

The example agent is a unit converter. It knows length, mass, volume and temperature. Leave out the target unit (`convert 10 km`) and it asks `To which unit?`. Answer `miles` on the same task, and it finishes the job. That shows a multi-turn A2A task (`INPUT_REQUIRED`, then `COMPLETED`).

## What's inside

Only four files are about A2A:

| File | What it does |
|---|---|
| [`routes/a2a.php`](routes/a2a.php) | One `Route::a2a(...)` line mounts the card and both endpoints |
| [`app/A2A/UnitConverterAgentCard.php`](app/A2A/UnitConverterAgentCard.php) | The Agent Card: name, skills, capabilities |
| [`app/A2A/UnitConverterExecutor.php`](app/A2A/UnitConverterExecutor.php) | Turns a message into task events (working, artifact, completed) |
| [`app/A2A/UnitConverter.php`](app/A2A/UnitConverter.php) | The agent's actual logic: plain PHP, with no A2A in it |

Everything else is a normal Laravel 13 app.

## Make it your own

1. **Replace the logic.** Edit `app/A2A/UnitConverter.php`, or write a new class that calls an LLM, your database or an API. The executor only needs a string back.
2. **Update the card.** Change the name, description and skills in `UnitConverterAgentCard.php`. Other agents read these to decide whether to call you.
3. **Or start a fresh agent.** `php artisan a2a:make-executor Support` writes `app/A2A/SupportExecutor.php`. Then point the route at it.
4. **Check the card.** `php artisan a2a:card` prints it as served and validates it.

## Tests

```bash
php artisan test
```

[`tests/Feature/UnitConverterAgentTest.php`](tests/Feature/UnitConverterAgentTest.php) calls the agent the way another agent would: over JSON-RPC and HTTP+JSON, with streaming and with a multi-turn task. Copy its helpers when you test your own agent.

## Going to production

- **Protect it.** Add `->middleware('auth:sanctum')` (or any auth) to the route in `routes/a2a.php`. The Agent Card stays public, which discovery needs. Tasks are scoped to the signed-in user.
- **Long-running work.** Set `A2A_RUNNER=queued` so executors run on your queue workers instead of in the web request. See [queued execution](https://praveendias1180.github.io/a2a-php/guides/laravel-queues/).
- **Streaming behind nginx.** Turn off response buffering for the A2A routes. See [SSE with nginx and PHP-FPM](https://praveendias1180.github.io/a2a-php/guides/laravel-sse-nginx/).
- **Clean up old tasks.** Schedule `php artisan a2a:prune`.

## Learn more

- [Laravel guide](https://praveendias1180.github.io/a2a-php/guides/laravel/)
- [A2A in five minutes](https://praveendias1180.github.io/a2a-php/concepts/a2a-in-five-minutes/)
- [A2A PHP SDK on GitHub](https://github.com/praveendias1180/a2a-php)
- [The A2A specification](https://a2a-protocol.org/latest/specification/)

## License

Apache-2.0. The Laravel skeleton this starts from is MIT-licensed.
