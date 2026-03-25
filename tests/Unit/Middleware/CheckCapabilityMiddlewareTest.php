<?php

namespace PhucBui\Chat\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Http\Middleware\CheckCapabilityMiddleware;
use PhucBui\Chat\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckCapabilityMiddlewareTest extends TestCase
{
    protected CheckCapabilityMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckCapabilityMiddleware(app(ChatManager::class));
    }

    public function test_passes_when_capability_true()
    {
        config(['chat.actors.client.capabilities.can_initiate_chat' => true]);

        $request = Request::create('/chat');
        $request->merge(['chat_actor_name' => 'client']);

        $next = function ($req) {
            return 'passed';
        };

        $result = $this->middleware->handle($request, $next, 'can_initiate_chat');

        $this->assertEquals('passed', $result);
    }

    public function test_aborts_403_when_capability_false()
    {
        config(['chat.actors.client.capabilities.can_initiate_chat' => false]);

        $request = Request::create('/chat');
        $request->merge(['chat_actor_name' => 'client']);

        try {
            $this->middleware->handle($request, function () {}, 'can_initiate_chat');
            $this->fail('Expected HttpException 403 was not thrown');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
