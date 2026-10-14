<?php

use Illuminate\Support\Facades\Route;

// A plain page for people who open the app in a browser. Agents use /.well-known/agent-card.json.
Route::get('/', function () {
    return response()->json([
        'agent' => config('app.name'),
        'agent_card' => url('/.well-known/agent-card.json'),
        // Single quotes: JSON would show double quotes as \", which breaks the command when copied.
        'try_it' => 'a2a send -a '.url('/')." --stream 'convert 10 km to miles'",
        'docs' => 'https://praveendias1180.github.io/a2a-php/guides/laravel/',
    ], options: JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
});
