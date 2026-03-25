<?php

namespace PhucBui\Chat\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Http\Middleware\ResolveActorMiddleware;
use PhucBui\Chat\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResolveActorMiddlewareTest extends TestCase
{
    protected ResolveActorMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ResolveActorMiddleware(app(ChatManager::class));
    }

    public function test_resolves_actor_and_sets_request_merge()
    {
        $actor = $this->createActorUser('client');
        $this->actingAs($actor);

        $request = Request::create('/chat');
        
        $next = function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return 'passed';
        };

        $result = $this->middleware->handle($request, $next, 'client');

        $this->assertEquals('passed', $result);
        $this->assertNotNull($capturedRequest->input('chat_actor'));
        $this->assertEquals($actor->id, $capturedRequest->input('chat_actor')->id);
        $this->assertEquals('client', $capturedRequest->input('chat_actor_name'));
    }

    public function test_aborts_401_when_unauthenticated()
    {
        // Not resolving the actor so it returns null
        app(ChatManager::class)->resolveActorUsing('admin', fn() => null);

        $request = Request::create('/chat');

        try {
            $this->middleware->handle($request, function () {}, 'admin');
            $this->fail('Expected HttpException 401 was not thrown');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
        }
    }

    public function test_aborts_403_when_wrong_actor_type()
    {
        // Actor resolved as client, but trying to access as admin which might fail isActorType
        $actor = $this->createActorUser('client');
        app(ChatManager::class)->resolveActorUsing('admin', fn() => $actor); // Force resolve as admin but isActorType will fail
        app(ChatManager::class)->matchActorUsing('admin', fn() => false);

        $request = Request::create('/chat');

        try {
            $this->middleware->handle($request, function () {}, 'admin');
            $this->fail('Expected HttpException 403 was not thrown');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
