# Integration Guide — phucbui/laravel-chat

> Hướng dẫn từng bước tích hợp package vào host Laravel project.

## Bước 1: Cài đặt

### Từ Packagist (khi publish)
```bash
composer require phucbui/laravel-chat
```

### Từ local path (development)
Thêm vào `composer.json` gốc:
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

## Bước 2: Publish & Migrate

```bash
php artisan chat:install
php artisan migrate
php artisan chat:seed-roles
```

Lệnh `chat:install` sẽ publish:
- `config/chat.php` — cấu hình chính
- Database migrations — 7 tables
- Views (tùy chọn) — Blade templates

## Bước 3: Implement Interface trên User Model

Mỗi model tham gia chat cần implement `ChatActorInterface` và dùng trait `HasChat`:

```php
<?php

namespace App\Models;

use PhucBui\Chat\Contracts\ChatActorInterface;
use PhucBui\Chat\Traits\HasChat;

class User extends Authenticatable implements ChatActorInterface
{
    use HasChat;

    // HasChat trait tự động cung cấp:
    // - getChatDisplayName(): string
    // - getChatAvatar(): ?string
    // - chatRooms() relationship
    // - chatParticipations() relationship
    // - chatMessages() relationship
    // - chatBlockedUsers() relationship
}
```

### Multi-table auth (nhiều bảng)

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

## Bước 4: Register Actor Resolvers

Trong `AppServiceProvider::boot()`:

### Single-table + Roles (Spatie Permission)

```php
use PhucBui\Chat\Chat;

public function boot(): void
{
    // Matcher: xác định user thuộc actor nào
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
    return auth('admin')->user(); // Guard riêng cho admin
});

Chat::resolveActorUsing('customer', function ($guard) {
    return auth('customer')->user();
});

// Matcher mặc định dùng instanceof, không cần register thêm
// vì mỗi actor dùng model class khác nhau
```

## Bước 5: Cấu hình config/chat.php

### Actors

Chỉnh sửa actors cho phù hợp hệ thống:

```php
'actors' => [
    'admin' => [
        'model' => 'App\\Models\\User',       // hoặc 'App\\Models\\Admin'
        'guard' => 'sanctum',                  // hoặc 'admin'
        'middleware' => ['auth:sanctum'],       // hoặc ['auth:admin']
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

Nếu hệ thống **đã có notification system riêng**:

```php
// Option A: Tắt hoàn toàn, dùng Events
'notifications' => [
    'enabled' => false,
],

// Trong EventServiceProvider:
use PhucBui\Chat\Events\MessageSent;

protected $listen = [
    MessageSent::class => [
        \App\Listeners\SendChatNotification::class,
    ],
];
```

```php
// Option B: Dùng custom notification class
'notifications' => [
    'enabled' => true,
    'notification_class' => \App\Notifications\ChatNotification::class,
],
```

## Bước 6: Test

### API Endpoints

Routes được tự động generate. Kiểm tra bằng:

```bash
php artisan route:list --path=chat
```

### Quick Test (Postman/curl)

```bash
# Lấy danh sách rooms (cần auth token)
curl -H "Authorization: Bearer {token}" \
     GET http://localhost/api/chat/rooms

# Tạo direct room
curl -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"target_id": 2, "target_type": "App\\Models\\User"}' \
     POST http://localhost/api/chat/rooms

# Gửi tin nhắn
curl -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"body": "Hello!", "type": "text"}' \
     POST http://localhost/api/chat/rooms/1/messages
```

---

## Tùy chỉnh nâng cao

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

### Thêm Actor mới

Chỉ cần thêm entry mới vào config `actors`:

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

Và register matcher:
```php
Chat::matchActorUsing('sales', fn($user) => $user->hasRole('sales'));
```

Routes sẽ tự động sinh cho actor `sales` tại `/api/sales/chat/*`.

---

## Checklist tích hợp

- [ ] `composer require phucbui/laravel-chat`
- [ ] `php artisan chat:install`
- [ ] `php artisan migrate`
- [ ] `php artisan chat:seed-roles`
- [ ] Implement `ChatActorInterface` + `HasChat` trên User model(s)
- [ ] Register actor matchers trong `AppServiceProvider`
- [ ] Cấu hình `config/chat.php` (actors, driver, notifications)
- [ ] Set `.env` variables (`CHAT_DRIVER`, socket credentials)
- [ ] Test API endpoints (`php artisan route:list --path=chat`)
