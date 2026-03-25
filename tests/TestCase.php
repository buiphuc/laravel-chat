<?php

declare(strict_types=1);

namespace PhucBui\Chat\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PhucBui\Chat\ChatServiceProvider;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PhucBui\Chat\Contracts\ChatActorInterface;
use PhucBui\Chat\Traits\HasChat;
use PhucBui\Chat\ChatManager;

class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function getPackageProviders($app): array
    {
        return [
            ChatServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Default test config for actors
        $app['config']->set('chat.actors', [
            'admin' => [
                'model' => TestUser::class,
                'capabilities' => [
                    'can_initiate_chat' => true,
                    'can_see_all_rooms' => false,
                    'can_create_group' => true,
                    'can_manage_participants' => true,
                    'can_change_roles' => false,
                    'can_receive_auto_routing' => true,
                    'can_review_reports' => false,
                    'can_block_users' => true,
                    'can_search_messages' => true,
                ],
            ],
            'client' => [
                'model' => TestUser::class,
                'route_prefix' => 'api/chat',
                'capabilities' => [
                    'can_initiate_chat' => true,
                    'can_see_all_rooms' => false,
                    'can_create_group' => false,
                    'can_manage_participants' => false,
                    'can_change_roles' => false,
                    'can_receive_auto_routing' => false,
                    'can_review_reports' => false,
                    'can_block_users' => true,
                    'can_search_messages' => true,
                ],
            ],
            'super_admin' => [
                'model' => TestUser::class,
                'capabilities' => [
                    'can_initiate_chat' => true,
                    'can_see_all_rooms' => true,
                    'can_create_group' => true,
                    'can_manage_participants' => true,
                    'can_change_roles' => true,
                    'can_receive_auto_routing' => false,
                    'can_review_reports' => true,
                    'can_block_users' => true,
                    'can_search_messages' => true,
                ],
            ]
        ]);
        
        $app['config']->set('chat.chat_types.allowed_pairs', [
            ['client', 'admin'],
            ['admin', 'admin'],
            ['client', 'client'],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Create a dummy users table for TestUser
        $this->artisan('migrate', ['--database' => 'testing'])->run();
        
        \Illuminate\Support\Facades\Schema::create('test_users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Create a user and register it as a specific actor type.
     */
    protected function createActorUser(string $actorName = 'client'): TestUser
    {
        $user = TestUser::create([
            'name' => "{$actorName} User " . uniqid(),
            'email' => "{$actorName}_" . uniqid() . "@example.com",
            'password' => bcrypt('password'),
        ]);

        app(ChatManager::class)->matchActorUsing(
            $actorName,
            // Match by checking if email starts with the actor name
            fn($u) => $u instanceof TestUser && str_starts_with($u->email, $actorName . '_')
        );

        app(ChatManager::class)->resolveActorUsing($actorName, function ($authUser) {
            return $authUser ?? auth()->user();
        });

        return $user;
    }
}

class TestUser extends Authenticatable implements ChatActorInterface
{
    use HasChat;

    protected $table = 'test_users';
    protected $guarded = [];
}
