# Integration Guide — phucbui/laravel-chat

> Step-by-step guide to integrate the package into a host Laravel project.

## Step 1: Install

### From Packagist (when published)
```bash
composer require phucbui/laravel-chat
```

### From local path (development)
Add to root `composer.json`:
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/laravel-chat"
    }
  ],
  "require": {
    "phucbui/laravel-chat": "*"
  }
}
```
```bash
composer update phucbui/laravel-chat
```

## Step 2: Publish & Migrate

```bash
php artisan chat:install
php artisan migrate
php artisan chat:seed-roles
```

The `chat:install` command will publish:
- `config/chat.php` — main configuration
- Database migrations — 7 tables
- Views (optional) — Blade templates

## Step 3: Implement Interface on User Model

Each model participating in chat must implement `ChatActorInterface` and use the `HasChat` trait:

```php
<?php

namespace App\Models;

use PhucBui\Chat\Contracts\ChatActorInterface;
use PhucBui\Chat\Traits\HasChat;

class User extends Authenticatable implements ChatActorInterface
{
    use HasChat;

    // HasChat trait automatically provides:
    // - getChatDisplayName(): string
    // - getChatAvatar(): ?string
    // - chatRooms() relationship
    // - chatParticipations() relationship
    // - chatMessages() relationship
    // - chatBlockedUsers() relationship
}
```

### Multi-table auth (separate tables)

```php
// app/Models/Admin.php
class Admin extends Authenticatable implements ChatActorInterface
{
    use HasChat;
}

// app/Models/Customer.php
class Customer extends Authenticatable implements ChatActorInterface
{
    use HasChat;
}
```

## Step 4: Register Actor Resolvers

In `AppServiceProvider::boot()`:

### Single-table + Roles (Spatie Permission)

```php
use PhucBui\Chat\Chat;

public function boot(): void
{
    // Matcher: determine which actor a user belongs to
    Chat::matchActorUsing('super_admin', fn($user) => $user->hasRole('super_admin'));
    Chat::matchActorUsing('admin', fn($user) => $user->hasAnyRole(['admin', 'editor']));
    Chat::matchActorUsing('client', fn($user) => $user->hasRole('member'));

    // Display name & avatar
    Chat::resolveDisplayNameUsing(fn($user) => $user->name);
    Chat::resolveAvatarUsing(fn($user) => $user->avatar_url);
}
```

### Multi-table auth

```php
Chat::resolveActorUsing('admin', function ($guard) {
    return auth('admin')->user(); // Separate guard for admin
});

Chat::resolveActorUsing('customer', function ($guard) {
    return auth('customer')->user();
});

// Default matcher uses instanceof, no additional registration needed
// since each actor uses a different model class
```

## Step 5: Configure config/chat.php

### Actors

Customize actors for your system:

```php
'actors' => [
    'admin' => [
        'model' => 'App\\Models\\User',       // or 'App\\Models\\Admin'
        'guard' => 'sanctum',                  // or 'admin'
        'middleware' => ['auth:sanctum'],       // or ['auth:admin']
        'route_prefix' => 'api/admin/chat',
        'capabilities' => [
            'can_initiate_chat' => true,
            'can_create_group' => true,
            // ...
        ],
    ],
],
```

### Socket Driver

```env
# .env
CHAT_DRIVER=reverb    # reverb | socketio | pusher
```

### Notifications

If your system **already has its own notification system**:

```php
// Option A: Disable completely, use Events
'notifications' => [
    'enabled' => false,
],

// In EventServiceProvider:
use PhucBui\Chat\Events\MessageSent;

protected $listen = [
    MessageSent::class => [
        \App\Listeners\SendChatNotification::class,
    ],
];
```

```php
// Option B: Use custom notification class
'notifications' => [
    'enabled' => true,
    'notification_class' => \App\Notifications\ChatNotification::class,
],
```

## Step 6: Test

### API Endpoints

Routes are auto-generated. Verify with:

```bash
php artisan route:list --path=chat
```

### Quick Test (Postman/curl)

```bash
# List rooms (requires auth token)
curl -H "Authorization: Bearer {token}" \
     GET http://localhost/api/chat/rooms

# Create direct room
curl -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"target_id": 2, "target_type": "App\\Models\\User"}' \
     POST http://localhost/api/chat/rooms

# Send message
curl -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"body": "Hello!", "type": "text"}' \
     POST http://localhost/api/chat/rooms/1/messages
```

---

## Advanced Customization

### Override Repository

```php
// app/Repositories/CustomChatRoomRepository.php
class CustomChatRoomRepository extends ChatRoomRepository
{
    public function getRoomsByActor(Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        // Custom logic
    }
}

// AppServiceProvider
$this->app->bind(
    ChatRoomRepositoryInterface::class,
    CustomChatRoomRepository::class
);
```

### Customize Table Names

```php
'table_names' => [
    'rooms' => 'my_chat_rooms',
    'messages' => 'my_chat_messages',
    // ...
],
```

### Add a New Actor

Simply add a new entry to config `actors`:

```php
'sales' => [
    'model' => 'App\\Models\\User',
    'guard' => 'sanctum',
    'middleware' => ['auth:sanctum'],
    'route_prefix' => 'api/sales/chat',
    'capabilities' => [
        'can_initiate_chat' => true,
        'can_create_group' => false,
        // ...
    ],
],
```

And register the matcher:
```php
Chat::matchActorUsing('sales', fn($user) => $user->hasRole('sales'));
```

Routes will be automatically generated for the `sales` actor at `/api/sales/chat/*`.

---

## Integration Checklist

- [ ] `composer require phucbui/laravel-chat`
- [ ] `php artisan chat:install`
- [ ] `php artisan migrate`
- [ ] `php artisan chat:seed-roles`
- [ ] Implement `ChatActorInterface` + `HasChat` on User model(s)
- [ ] Register actor matchers in `AppServiceProvider`
- [ ] Configure `config/chat.php` (actors, driver, notifications)
- [ ] Set `.env` variables (`CHAT_DRIVER`, socket credentials)
- [ ] Test API endpoints (`php artisan route:list --path=chat`)
