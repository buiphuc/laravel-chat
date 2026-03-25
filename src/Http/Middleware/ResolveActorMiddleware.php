<?php

namespace PhucBui\Chat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PhucBui\Chat\ChatManager;

class ResolveActorMiddleware
{
    public function __construct(protected ChatManager $chatManager)
    {
    }

    public function handle(Request $request, Closure $next, string $actorName)
    {
        $actor = $this->chatManager->resolveActor($actorName);

        if (!$actor) {
            abort(401, 'Unauthenticated.');
        }

        // Verify the user matches this actor type
        if (!$this->chatManager->isActorType($actorName, $actor)) {
            abort(403, 'Unauthorized for this actor type.');
        }

        // Store actor info on request
        $request->merge([
            'chat_actor' => $actor,
            'chat_actor_name' => $actorName,
        ]);

        return $next($request);
    }
}
