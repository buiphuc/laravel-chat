<?php

declare(strict_types=1);

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Tests\TestUser;
use InvalidArgumentException;

class ChatManagerTest extends TestCase
{
    private ChatManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        // The service provider binds ChatManager as a singleton
        $this->manager = app(ChatManager::class);
    }

    public function test_has_capability_returns_true_for_enabled_capability(): void
    {
        // Admin has can_create_group = true in TestCase setup
        $this->assertTrue($this->manager->hasCapability('admin', 'can_create_group'));
    }

    public function test_has_capability_returns_false_for_disabled_capability(): void
    {
        // Client has can_create_group = false in TestCase setup
        $this->assertFalse($this->manager->hasCapability('client', 'can_create_group'));
    }

    public function test_has_capability_returns_false_for_undefined_capability(): void
    {
        // Unknown capabilities are false by default
        $this->assertFalse($this->manager->hasCapability('admin', 'unknown_power'));
    }

    public function test_has_capability_returns_false_for_unknown_actor(): void
    {
        $this->assertFalse($this->manager->hasCapability('unknown_actor', 'can_create_group'));
    }

    public function test_detect_actor_type_uses_registered_matchers(): void
    {
        // ARRANGE: create user matching 'admin' using our helper
        $admin = $this->createActorUser('admin');
        
        // ACT
        $actorName = $this->manager->detectActorType($admin);

        // ASSERT
        $this->assertEquals('admin', $actorName);
    }

    public function test_detect_actor_type_returns_null_when_no_match(): void
    {
        // ARRANGE: A user model not registered in config (like generic authenticatable)
        $user = new class extends \Illuminate\Foundation\Auth\User {
            // An anonymous class
        };

        // ACT
        $actorName = $this->manager->detectActorType($user);

        // ASSERT
        $this->assertNull($actorName);
    }

    public function test_resolve_actor_using_callback_returns_correct_user(): void
    {
        // ARRANGE
        $user = $this->createActorUser('client');
        
        $this->manager->resolveActorUsing('client', function () use ($user) {
            return $user;
        });

        // ACT
        $resolved = $this->manager->resolveActor('client', 'sanctum');

        // ASSERT
        $this->assertNotNull($resolved);
        $this->assertEquals($user->id, $resolved->id);
    }

    public function test_resolve_actor_uses_auth_fallback(): void
    {
        // ACT - Since no resolver was registered via resolveActorUsing, it uses Auth facade
        // Here it returns null since no user is logged in
        $resolved = $this->manager->resolveActor('client', 'sanctum');
        
        // ASSERT
        $this->assertNull($resolved);
    }

    public function test_resolve_actor_returns_null_if_config_not_found(): void
    {
        $this->assertNull($this->manager->resolveActor('non_existent'));
    }

    public function test_can_chat_with_validates_against_allowed_pairs(): void
    {
        // Allowed pairs config: ['client', 'admin'], ['admin', 'admin'], ['client', 'client']
        
        $this->assertTrue($this->manager->canChatWith('client', 'admin'));
        $this->assertTrue($this->manager->canChatWith('admin', 'client')); // Order doesn't matter
        $this->assertTrue($this->manager->canChatWith('client', 'client'));
        $this->assertTrue($this->manager->canChatWith('admin', 'admin'));
        
        // super_admin isn't in pairs
        $this->assertFalse($this->manager->canChatWith('client', 'super_admin'));
    }
}
