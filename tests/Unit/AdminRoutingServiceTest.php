<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Services\AdminRoutingService;
use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Services\RoomService;

class AdminRoutingServiceTest extends TestCase
{
    protected AdminRoutingService $routingService;
    protected RoomService $roomService;

    protected function setUp(): void
    {
        parent::setUp();
        ChatRole::create(['name' => 'owner', 'display_name' => 'Owner', 'permissions' => []]);
        ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);
        $this->routingService = app(AdminRoutingService::class);
        $this->roomService = app(RoomService::class);

        // Enable routing in config for tests
        config(['chat.auto_routing.enabled' => true]);
        config(['chat.auto_routing.to_actor' => 'admin']);
    }

    public function test_least_busy_routing()
    {
        config(['chat.auto_routing.strategy' => 'least_busy']);

        $client = $this->createActorUser('client');
        
        $admin1 = $this->createActorUser('admin');
        $admin2 = $this->createActorUser('admin');

        // Admin 1 has 1 room
        $client2 = $this->createActorUser('client');
        $this->roomService->findOrCreateDirectRoom($admin1, $client2);

        // Admin 2 has 0 rooms, should be picked
        $bestAdmin = $this->routingService->findBestAdmin($client);

        $this->assertNotNull($bestAdmin);
        $this->assertEquals($admin2->id, $bestAdmin->id);
    }

    public function test_last_contacted_routing()
    {
        config(['chat.auto_routing.strategy' => 'last_contacted']);

        $client = $this->createActorUser('client');
        
        $admin1 = $this->createActorUser('admin');
        $admin2 = $this->createActorUser('admin');

        // Client chatted with Admin 1 in the past
        $this->roomService->findOrCreateDirectRoom($client, $admin1);

        $bestAdmin = $this->routingService->findBestAdmin($client);

        $this->assertNotNull($bestAdmin);
        // Sometimes it might return admin1, wait findOrCreateDirectRoom creates it with now()
        $this->assertEquals($admin1->id, $bestAdmin->id);
    }

    public function test_round_robin_routing()
    {
        config(['chat.auto_routing.strategy' => 'round_robin']);

        $client = $this->createActorUser('client');
        
        $admin1 = $this->createActorUser('admin');
        $admin2 = $this->createActorUser('admin');

        // Admin 1 was assigned a room 5 minutes ago
        $room = $this->roomService->findOrCreateDirectRoom($admin1, $this->createActorUser('client'));
        $room->update(['created_at' => now()->subMinutes(5)]);

        // Admin 2 was assigned a room 1 minute ago
        $room2 = $this->roomService->findOrCreateDirectRoom($admin2, $this->createActorUser('client'));
        $room2->update(['created_at' => now()->subMinute()]);

        // Should pick admin 1 because it's the oldest assigned
        $bestAdmin = $this->routingService->findBestAdmin($client);

        $this->assertNotNull($bestAdmin);
        $this->assertEquals($admin1->id, $bestAdmin->id);
    }
}
