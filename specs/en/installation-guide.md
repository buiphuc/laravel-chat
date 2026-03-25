# Installation & Setup Guide

This guide will walk you through the detailed setup, actor configurations, and practical API usage of `phucbui/laravel-chat`.

## 1. Configuration & Setup

## 1. Detailed `config/chat.php` Explanation

When you publish the configuration file, you will encounter multiple sections. Here is what they control:

### 1.1 `actors` (Entity Identification)
The core of the system. Instead of limiting to "Users", the package treats anyone as an "Actor". You can have `admins`, `customers`, etc.
- `model`: Class of the representative Model (e.g., `App\Models\User`).
- `guard`: The Auth guard used for this Actor (`sanctum`, `web`...).
- `middleware`: Middlewares automatically attached to this Actor's Routes.
- `route_prefix`: Declares the dynamic API route prefix, e.g., `api/admin/chat`.
- `capabilities`: Macro-level permissions for the Actor (booleans):
  - `can_initiate_chat`: Is allowed to proactively start a chat room.
  - `can_see_all_rooms`: (For super_admin) can see and monitor rooms they aren't part of.
  - `can_create_group`: Can create personal Groups.
  - `can_receive_auto_routing`: Can this Actor be assigned to an incoming chat queue from random clients?
  - `can_block_users`, `can_review_reports`: Assign moderation power against spam.

### 1.2 `chat_types` (Communication Rules)
- `allowed_pairs`: Array declaring which Actors can talk to which. 
  *Example: `['client', 'admin']` (Clients talk to Admins), `['admin', 'admin']` (Admins talk internally).*
- `group`: boolean. Globally enable or disable group chat functionality.

### 1.3 `auto_routing` (Work Distribution)
Automatically finds an Admin for a Customer when they start a chat for the first time:
- `enabled`: Toggle auto-routing.
- `from_actor`: The incoming actor (usually `client`).
- `to_actor`: The receiving actor (usually `admin`).
- `strategy`: The algorithm to choose the receiver: `least_busy` (fewest active chats), `round_robin` (turn by turn), `last_contacted` (reconnects them to their previous admin).

### 1.4 Auxiliary Features
- `attachments`: File upload config (storage `disk`, Max size = 10MB, `allowed_types`).
- `block_report`: Toggle the ability to Ban users and Report toxic messages.
- `notifications`: Push Offline Notifications via Email/FCM (Fully overridable via custom notification class).
- `default_roles`: System roles used OVER Room Level (owner, admin, member) governing who can kick/add members inside a group chat.

---

## 2. Complete Configuration Sample Cases

### Sample 1: Customer Support System (CSKH)
**Requirement**: `client` from `customers` table can only chat with `admin` support agents. Agents can chat with their Manager (`super_admin`).

```php
'actors' => [
    'super_admin' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/super-admin/chat',
        'capabilities' => ['can_see_all_rooms' => true, 'can_review_reports' => true],
    ],
    'admin' => [
        'model' => App\Models\User::class, // Same Model but different Role
        'route_prefix' => 'api/admin/chat',
        'capabilities' => [
            'can_receive_auto_routing' => true, // Will receive clients
            'can_create_group' => true,
        ],
    ],
    'client' => [
        'model' => App\Models\Customer::class, // Multi-table auth integration
        'route_prefix' => 'api/chat',
        'capabilities' => [
            'can_create_group' => false, // Clients cannot create groups
            'can_initiate_chat' => true, // Can start 1v1 with Admin
            'can_receive_auto_routing' => false,
        ],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['client', 'admin'],
        ['admin', 'admin'],
        ['admin', 'super_admin'], // Client CANNOT chat directly with super_admin
    ],
],
'auto_routing' => [
    'enabled' => true,
    'from_actor' => 'client',
    'to_actor' => 'admin',
    'strategy' => 'least_busy', // Pick the least busy agent
]
```

### Sample 2: Internal Social Network (Company Slack)
**Requirement**: Meaningful internal network. Everyone uses the `users` table and anyone can create a group chat and 1v1 anyone else freely.

```php
'actors' => [
    'employee' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/chat',
        'capabilities' => [
            'can_initiate_chat' => true,
            'can_create_group' => true,
            'can_manage_participants' => true, // Can add/kick people from their groups
            'can_block_users' => true, 
        ],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['employee', 'employee'], // Free internal chatting
    ],
    'group' => true, // Unlock group chats
],
'auto_routing' => [
    'enabled' => false, // No support-line functionality needed
]
```

### Sample 3: Multi-sided Platform (E-commerce / Booking)
**Requirement**: The platform has 3 sides: `admin` (Platform Operator), `client` (Vendor/Shop), `customer` (End-user). Customers can chat with Shops or complaining to Platform Admins. Shops can chat with Customers or get support from Admins. Auto-routing is disabled as users proactively choose who to message (e.g. clicking "Contact Shop").

```php
'actors' => [
    'admin' => [
        'model' => App\Models\User::class, // Platform Staff
        'route_prefix' => 'api/admin/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => true, 'can_block_users' => true],
    ],
    'client' => [
        'model' => App\Models\Vendor::class, // The Shops table
        'route_prefix' => 'api/vendor/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => false],
    ],
    'customer' => [
        'model' => App\Models\Customer::class, // Retail Customers table
        'route_prefix' => 'api/customer/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => false],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['customer', 'client'], // Customer chats with Shop
        ['customer', 'admin'],  // Customer complains to Platform
        ['client', 'admin'],    // Shop asks Platform for support
        ['admin', 'admin'],     // Internal Platform staff
    ],
    'group' => true, // Example: Can create a group with Customer, Shop, and Admin to resolve disputes
],
'auto_routing' => [
    'enabled' => false, // User manually clicks "Contact Shop X" or "Contact Admin Y"
]
```

---

## 3. Model Setup & Register Resolvers

Any Eloquent Model that participates in a chat must implement the `ChatActorInterface` and utilize the `HasChat` trait.

```php
namespace App\Models;

use PhucBui\Chat\Contracts\ChatActorInterface;
use PhucBui\Chat\Traits\HasChat;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements ChatActorInterface
{
    use HasChat;
    
    // ...
}
```

### Register Resolvers

To let the dynamic routing know exactly *who* is making the request, register your actor matching logic within your application's `AppServiceProvider`:

```php
use PhucBui\Chat\ChatFacade as Chat;

public function boot(): void
{
    // Match the 'admin' actor if the user has the explicit Spatie role
    Chat::matchActorUsing('admin', function ($user) {
        return $user->hasRole('admin');
    });

    // Match the 'client' actor
    Chat::matchActorUsing('client', function ($user) {
        return $user->hasRole('member');
    });

    // Optional: Determine how Avatar and Display Names are extracted globally
    Chat::resolveDisplayNameUsing(fn($user) => $user->name ?? 'Unknown');
    Chat::resolveAvatarUsing(fn($user) => $user->avatar_url ?? null);
}
```

---

## 2. Detailed API Usage

Below are examples showing how to interact with the auto-generated APIs. Assume we are acting as a `client` whose route prefix is configured to `/api/chat`.

### Create a Direct Room (1v1)
**Endpoint:** `POST /api/chat/rooms`

**Payload:**
```json
{
    "type": "direct",
    "participant_type": "App\\Models\\User",
    "participant_id": 2
}
```

### Create a Group Room
Requires the actor to have the `can_create_group` capability.

**Endpoint:** `POST /api/chat/rooms`

**Payload:**
```json
{
    "type": "group",
    "name": "Project Alpha Discussion",
    "metadata": {
        "department": "Engineering"
    }
}
```

### Send a Message 
Send a rich text message to a room.

**Endpoint:** `POST /api/chat/rooms/{room_id}/messages`

**Payload:**
```json
{
    "type": "text",
    "body": "Hello team, let's review the deployment plan."
}
```

### Add a Participant to Group
Requires the room member to have the `add_member` ChatRole permission OR global `can_manage_participants` capability.

**Endpoint:** `POST /api/chat/rooms/{room_id}/participants`

**Payload:**
```json
{
    "actor_type": "App\\Models\\User",
    "actor_id": 5
}
```

### Read Receipts
Track reading progress. When a user opens the chat, call this to update their `last_read_at` cursor.

**Endpoint:** `POST /api/chat/rooms/{room_id}/read`
*(No payload required)*

### Moderation: Block User
Block another user from sending immediate messages and direct rooms.

**Endpoint:** `POST /api/chat/users/{actor_id}/block`

**Payload:**
```json
{
    "target_type": "App\\Models\\User",
    "reason": "Spamming inappropriate links"
}
```

---

## 3. Broadcasting

`laravel-chat` fires events seamlessly. Ensure you have your broadcasting configured in Laravel's `.env`:

```env
BROADCAST_CONNECTION=reverb

# Or if using Pusher
PUSHER_APP_ID=your_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

**Channels you can listen to on your frontend Client:**

1. **User Channel** (For invitiations, global routing triggers)
   - Channel: `chat.user.{actor_type}.{actor_id}`
   - *Example:* `chat.user.App.Models.User.1`

2. **Room Channel** (For typing events, new messages, read receipts)
   - Channel: `chat.room.{room_id}`
   - *Example:* `chat.room.5`

**Events dispatched:**
- `MessageSent`
- `MessageRead`
- `UserTyping`
- `RoomCreated`
