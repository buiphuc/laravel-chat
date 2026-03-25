<?php

namespace PhucBui\Chat;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PhucBui\Chat\Commands\InstallCommand;
use PhucBui\Chat\Commands\SeedRolesCommand;
use PhucBui\Chat\Contracts\ChatServiceInterface;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatMessageRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoleRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatAttachmentRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatBlockedUserRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatReportRepositoryInterface;
use PhucBui\Chat\Drivers\ReverbDriver;
use PhucBui\Chat\Drivers\SocketIoDriver;
use PhucBui\Chat\Drivers\PusherDriver;
use PhucBui\Chat\Http\Middleware\ResolveActorMiddleware;
use PhucBui\Chat\Http\Middleware\CheckCapabilityMiddleware;
use PhucBui\Chat\Http\Middleware\ChatRoomAccessMiddleware;
use PhucBui\Chat\Repositories\ChatRoomRepository;
use PhucBui\Chat\Repositories\ChatMessageRepository;
use PhucBui\Chat\Repositories\ChatParticipantRepository;
use PhucBui\Chat\Repositories\ChatRoleRepository;
use PhucBui\Chat\Repositories\ChatAttachmentRepository;
use PhucBui\Chat\Repositories\ChatBlockedUserRepository;
use PhucBui\Chat\Repositories\ChatReportRepository;
use PhucBui\Chat\Services\ChatService;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chat.php', 'chat');

        $this->app->singleton(ChatManager::class, function ($app) {
            return new ChatManager();
        });

        $this->registerRepositories();
        $this->registerDriver();
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'chat');
        
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerCommands();
    }

    /**
     * Register repository bindings.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(ChatRoomRepositoryInterface::class, ChatRoomRepository::class);
        $this->app->bind(ChatMessageRepositoryInterface::class, ChatMessageRepository::class);
        $this->app->bind(ChatParticipantRepositoryInterface::class, ChatParticipantRepository::class);
        $this->app->bind(ChatRoleRepositoryInterface::class, ChatRoleRepository::class);
        $this->app->bind(ChatAttachmentRepositoryInterface::class, ChatAttachmentRepository::class);
        $this->app->bind(ChatBlockedUserRepositoryInterface::class, ChatBlockedUserRepository::class);
        $this->app->bind(ChatReportRepositoryInterface::class, ChatReportRepository::class);
    }

    /**
     * Register the socket driver based on config.
     */
    protected function registerDriver(): void
    {
        $this->app->singleton(SocketDriverInterface::class, function ($app) {
            $driver = config('chat.driver', 'reverb');

            return match ($driver) {
                'reverb' => new ReverbDriver(config('chat.drivers.reverb', [])),
                'socketio' => new SocketIoDriver(config('chat.drivers.socketio', [])),
                'pusher' => new PusherDriver(config('chat.drivers.pusher', [])),
                default => throw new \InvalidArgumentException("Unsupported chat driver: {$driver}"),
            };
        });
    }

    /**
     * Register service bindings.
     */
    protected function registerServices(): void
    {
        $this->app->bind(ChatServiceInterface::class, ChatService::class);
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/chat.php' => config_path('chat.php'),
            ], 'chat-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'chat-migrations');

            if (config('chat.views.publish', true)) {
                $this->publishes([
                    __DIR__ . '/../resources/views' => resource_path('views/vendor/chat'),
                ], 'chat-views');
            }

            $this->publishes([
                __DIR__ . '/../resources/lang' => resource_path('lang/vendor/chat'),
            ], 'chat-translations');
        }
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('chat.resolve_actor', ResolveActorMiddleware::class);
        $router->aliasMiddleware('chat.capability', CheckCapabilityMiddleware::class);
        $router->aliasMiddleware('chat.room_access', ChatRoomAccessMiddleware::class);
    }

    /**
     * Register routes dynamically based on actors config.
     */
    protected function registerRoutes(): void
    {
        $actors = config('chat.actors', []);

        foreach ($actors as $actorName => $actorConfig) {
            Route::prefix($actorConfig['route_prefix'] ?? "api/{$actorName}/chat")
                ->middleware(array_merge(
                    $actorConfig['middleware'] ?? [],
                    ["chat.resolve_actor:{$actorName}"]
                ))
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/../routes/chat.php');
                });
        }
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        if (config('chat.views.enabled', false)) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'chat');
        }
    }

    /**
     * Register artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SeedRolesCommand::class,
            ]);
        }
    }
}
