<?php

namespace PhucBui\Chat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PhucBui\Chat\ChatManager;

class CheckCapabilityMiddleware
{
    public function __construct(protected ChatManager $chatManager)
    {
    }

    public function handle(Request $request, Closure $next, string $capability)
    {
        $actorName = $request->input('chat_actor_name');

        if (!$actorName || !$this->chatManager->hasCapability($actorName, $capability)) {
            abort(403, "You do not have the capability: {$capability}");
        }

        return $next($request);
    }
}
