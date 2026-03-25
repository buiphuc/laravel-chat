<?php

namespace PhucBui\Chat\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Http\Middleware\ChatRoomAccessMiddleware;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatRoomAccessMiddlewareTest extends TestCase
{
    protected ChatRoomAccessMiddleware $middleware;
    protected ChatRoom $room;
    protected $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ChatRoomAccessMiddleware(
            app(ChatManager::class),
            app(ChatParticipantRepositoryInterface::class)
        );
        ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);

        $this->actor = $this->createActorUser('client');
        $this->room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $this->actor->getMorphClass(),
            'created_by_id' => $this->actor->id,
        ]);
    }

    public function test_passes_if_super_admin()
    {
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]);

        $request = Request::create('/chat');
        $request->merge([
            'chat_actor' => $this->actor,
            'chat_actor_name' => 'admin',
        ]);
        $route = new Route('GET', '/chat/{room}', []);
        $route->bind($request);
        $route->setParameter('room', $this->room->id);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $result = $this->middleware->handle($request, function () {
            return 'passed';
        });

        $this->assertEquals('passed', $result);
    }

    public function test_passes_if_member()
    {
        config(['chat.actors.client.capabilities.can_see_all_rooms' => false]);
        app(ChatParticipantRepositoryInterface::class)->create([
            'room_id' => $this->room->id,
            'actor_type' => $this->actor->getMorphClass(),
            'actor_id' => $this->actor->id,
            'role_id' => ChatRole::first()->id,
            'joined_at' => now(),
        ]);

        $request = Request::create('/chat');
        $request->merge([
            'chat_actor' => $this->actor,
            'chat_actor_name' => 'client',
        ]);
        
        $route = new Route('GET', '/chat/{room}', []);
        $route->bind($request);
        $route->setParameter('room', $this->room->id);
        $request->setRouteResolver(fn() => $route);

        $result = $this->middleware->handle($request, function () {
            return 'passed';
        });

        $this->assertEquals('passed', $result);
    }

    public function test_aborts_403_if_not_member()
    {
        config(['chat.actors.client.capabilities.can_see_all_rooms' => false]);
        
        $request = Request::create('/chat');
        $request->merge([
            'chat_actor' => $this->actor,
            'chat_actor_name' => 'client',
        ]);
        
        $route = new Route('GET', '/chat/{room}', []);
        $route->bind($request);
        $route->setParameter('room', $this->room->id);
        $request->setRouteResolver(fn() => $route);

        try {
            $this->middleware->handle($request, function () {});
            $this->fail('Expected HttpException 403 was not thrown');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
