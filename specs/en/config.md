# Configuration Reference — phucbui/laravel-chat

> Comprehensive Reference and Explanation for all configurations in `config/chat.php`. This config file controls all macro behaviors of the Chat system, from automatic work distribution and permissions to Socket Servers.

---

## 1. `actors` (User Groups Identification)

This is the most critical declaration. It defines THE GROUPS OF PEOPLE who can participate in the chat. The system perfectly supports Multi-table (multiple tables like `users`, `admins`, `customers`) or Single-table (1 shared `users` table with Roles).

### Configuration keys for 1 Actor:
- `model` (string): The Full Class path to the Eloquent Model representing this group. Ex: `App\Models\Admin`.
- `guard` (string): The Auth Guard name used for API authentication. Ex: `sanctum`, `admin`, `web`.
- `middleware` (array): Middlewares that the API routes of this Actor will pass through. Ex: `['auth:sanctum']`.
- `route_prefix` (string): The system will **automatically generate API endpoints** for this group under this prefix domain. Ex: `api/admin/chat`.
- `is_match` (null): *Not applicable in config file*. Used at runtime via the `Chat::matchActorUsing()` command in AppServiceProvider to bind logic.

### `capabilities` (Macro Capabilities):
General functional permissions of this Actor group:
- `can_initiate_chat`: Allows the action of starting a 1-1 chat with someone else.
- `can_see_all_rooms`: Supervisory Rights (Super Admin), can view chat logs of other people's rooms even if they are not in the room.
- `can_create_group`: Has the right to click "Create New Group" and invite people to join.
- `can_manage_participants`: Has the right to remove/add members in their chat group (provided their role in the room is Owner/Admin).
- `can_receive_auto_routing`: This person **HAS CUSTOMER SUPPORT FUNCTIONS** (Ex: CS Agent). If `true`, they will enter the queue when a customer sends a message.
- `can_block_users`: Enables the Block User function (blocking them from spamming direct messages).
- `can_review_reports`: Allowed access to the system for tracking Spam Reports/Complaints about chat segments.
- `can_search_messages`: Unlocks the global message search API function.

---

## 2. `chat_types` (Communication Permissions)

Defines the boundaries between User Groups:
- `allowed_pairs` (array): Array defining **whether Actor A is allowed to chat with Actor B**. 
  - Declared as `[['client', 'admin'], ['admin', 'admin']]`.
  - Practical limitation: For example, if you DO NOT want a `client` to find an ID and send a message to another `client`, do not declare the pair `['client', 'client']`.
- `group` (bool): Global toggle. Enables or Disables the entire Group Chat feature for the application.

---

## 3. `auto_routing` (Automatic Call Center Routing)

Internal CS Call Center System. When a `from_actor` sends a direct message to system without knowing who to send it to, the system automatically scans the DB for an online/most suitable person from the `to_actor` group to map a 1-1 chat room for both sides.

- `enabled` (bool): Enable/Disable this module.
- `from_actor` (string): The group seeking help (Ex: `client`).
- `to_actor` (string): The group answering (Ex: `admin`).
- `strategy` (string): Algorithm to choose the suitable CS staff when there is a message from a customer:
  - `least_busy`: Find the `admin` staff with the fewest active chat sessions today. Avoids overloading 1 staff member.
  - `round_robin`: Distribute work evenly and sequentially.
  - `last_contacted`: Prioritizes regular customers, finding the staff who chatted with this customer last time (if still active).
- `fallback` (string): If the main algorithm fails (Ex: old staff quit), fallback to this algorithm (Default: `least_busy`).

---

## 4. Message Conveyor (Socket & DB)

### `driver` & `drivers` (Realtime Socket)
- `driver` (string): Default Realtime Connection (Takes from `.env`). Options: `reverb`, `pusher`, `socketio`.
- `drivers`: Specific Connection Array configuration for each socket server. Declares port, host, api keys for Pusher, Socket.IO, or Laravel Reverb.

### `messages` (Text Display Limits)
- `per_page` (int): Number of messages to load more when scrolling API Paginate. Optimizes DB quota.
- `max_length` (int): Blocks extremely long texts. Maximum character count for a text message.
- `search_enabled` (bool): Global toggle Enable/Disable Full Text search on Frontend.

---

## 5. `notifications` (Push Offline Notifications)

Smart logical Push protocol. If you chat and the other party **HAS CLOSED BROWSER / IS NOT ONLINE** via socket, the system will push Laravel Notification (Email or Firebase Push Noti). Configuration:

- `enabled` (bool): Enable branching notifications. If `false`, the system simply dispatches the `MessageSent` Event and leaves it to Developer to handle.
- `channels` (array): Channels to push. For example `['database', 'mail', 'fcm']` (FCM for mobile notifications).
- `notify_offline_only` (bool): `true` = Only push Notification when the user has actually closed the socket (Prevents spamming Notifications if they are actively chatting).
- `notification_class`: If you want to completely overwrite the Mail Interface / Push Notification Content, inject your application's Custom Class here. If left blank, it uses the package's Default Class.

---

## 6. `attachments` (Files & Attached Images)

- `enabled` (bool): Enable upload attachment file feature.
- `disk` (string): Disk Storage (Default `public`).
- `max_size` (int): Bytes / thousand (10240 = 10MB maximum).
- `allowed_types` (array): File filter. Default `['image/*', 'application/pdf', 'text/*']`.
- `path` (string): Separate subfolder to hold all chat images. 

---

## 7. Basic System Configurations

### `block_report`
Enable Moderation Module.
- `block_enabled`: Opens User Blocking API.
- `report_enabled`: Opens API to Report bad content to Admin.

### `default_roles`
Declares position names **INSIDE A GROUP CHAT ROOM**. 
- This data is used when default `Seed` into DB. It divides accompanying room Permissions like `owner` can destroy room `manage_room`, while a normal `member` only has `send_message`. (This is a Room internal Role, Not a Spatie system Auth Role).

### `table_names`
- Customize all 7 Prefix original Table names of the DB (in case the old DB has name collisions with the Package). You have the right to freely rename tables like `chat_rooms`, `chat_roles`, `chat_messages` to any name you like, the Package will still work perfectly!

---

## 8. Sample Configuration Cases

### Sample 1: Customer Support System (CSKH)
**Requirement**: Customers (`client`) only chat with support agents (`admin`). Agents receive automatic routed chats when customers first connect.

```php
'actors' => [
    'admin' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/admin/chat',
        'capabilities' => ['can_receive_auto_routing' => true, 'can_create_group' => true],
    ],
    'client' => [
        'model' => App\Models\Customer::class,
        'route_prefix' => 'api/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_receive_auto_routing' => false],
    ]
],
'chat_types' => [
    'allowed_pairs' => [['client', 'admin'], ['admin', 'admin']],
    'group' => true,
],
'auto_routing' => [
    'enabled' => true,
    'from_actor' => 'client',
    'to_actor' => 'admin',
    'strategy' => 'least_busy',
]
```

### Sample 2: Internal Social Network (Company Slack)
**Requirement**: All employees use the `users` table, can create group chats, and freely 1v1 anyone without support restrictions. Auto-routing is completely disabled.

```php
'actors' => [
    'employee' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/chat',
        'capabilities' => [
            'can_initiate_chat' => true,
            'can_create_group' => true,
            'can_manage_participants' => true,
            'can_block_users' => true, 
        ],
    ]
],
'chat_types' => [
    'allowed_pairs' => [['employee', 'employee']],
    'group' => true,
],
'auto_routing' => [
    'enabled' => false,
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
