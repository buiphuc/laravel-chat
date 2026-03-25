<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Actors Configuration
    |--------------------------------------------------------------------------
    |
    | Define all actor types that can participate in chat.
    | Each actor represents a group of users (admin, client, customer, sale, etc.)
    | Supports both multi-table auth (separate tables) and single-table + roles.
    |
    */
    'actors' => [
        'super_admin' => [
            'model' => 'App\\Models\\User',
            'guard' => 'sanctum',
            'middleware' => ['auth:sanctum'],
            'route_prefix' => 'api/super-admin/chat',
            'is_match' => null, // Set in ChatServiceProvider boot or override in AppServiceProvider
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
        ],

        'admin' => [
            'model' => 'App\\Models\\User',
            'guard' => 'sanctum',
            'middleware' => ['auth:sanctum'],
            'route_prefix' => 'api/admin/chat',
            'is_match' => null,
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
            'model' => 'App\\Models\\User',
            'guard' => 'sanctum',
            'middleware' => ['auth:sanctum'],
            'route_prefix' => 'api/chat',
            'is_match' => null,
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Actor Resolvers
    |--------------------------------------------------------------------------
    |
    | Closures cannot be stored in config files. Use ChatServiceProvider or
    | AppServiceProvider to register resolvers via Chat::resolveActorUsing().
    |
    | Example in AppServiceProvider::boot():
    |
    |   Chat::resolveActorUsing('admin', function ($guard) {
    |       $user = auth($guard)->user();
    |       return $user && $user->hasAnyRole(['admin', 'super_admin']) ? $user : null;
    |   });
    |
    |   Chat::matchActorUsing('admin', function ($user) {
    |       return $user->hasAnyRole(['admin', 'super_admin']);
    |   });
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Chat Types
    |--------------------------------------------------------------------------
    |
    | Define which actor pairs are allowed to chat with each other.
    | Also controls whether group chat is enabled.
    |
    */
    'chat_types' => [
        'allowed_pairs' => [
            ['client', 'admin'],
            ['client', 'super_admin'],
            ['admin', 'admin'],
            ['admin', 'super_admin'],
        ],
        'group' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-routing
    |--------------------------------------------------------------------------
    |
    | Automatically route client messages to the appropriate admin/supporter.
    |
    */
    'auto_routing' => [
        'enabled' => true,
        'from_actor' => 'client',
        'to_actor' => 'admin',
        'strategy' => 'last_contacted', // last_contacted | least_busy | round_robin
        'fallback' => 'least_busy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Socket Driver
    |--------------------------------------------------------------------------
    |
    | Choose between: reverb, socketio, pusher
    |
    */
    'driver' => env('CHAT_DRIVER', 'reverb'),

    'drivers' => [
        'reverb' => [],
        'socketio' => [
            'server_url' => env('SOCKETIO_SERVER_URL', 'http://localhost:3000'),
            'api_key' => env('SOCKETIO_API_KEY'),
        ],
        'pusher' => [
            'app_id' => env('PUSHER_APP_ID'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */
    'attachments' => [
        'enabled' => true,
        'disk' => 'public',
        'max_size' => 10240, // KB
        'allowed_types' => ['image/*', 'application/pdf', 'text/*'],
        'path' => 'chat-attachments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'per_page' => 50,
        'max_length' => 5000,
        'search_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | 3 chế độ hoạt động:
    |
    | 1. Package notification (mặc định):
    |    'enabled' => true, 'notification_class' => null
    |    → Sử dụng NewMessageNotification built-in
    |
    | 2. Custom notification class:
    |    'enabled' => true, 'notification_class' => \App\Notifications\YourNotification::class
    |    → Class phải nhận (ChatMessage $message, ChatRoom $room) trong constructor
    |
    | 3. Event-only (hệ thống đã có notification riêng):
    |    'enabled' => false
    |    → Package KHÔNG gửi notification, chỉ fire Events
    |    → Host system lắng nghe MessageSent event để gửi thông báo riêng
    |
    | Dù ở chế độ nào, Events (MessageSent, MessageRead...) LUÔN được fire.
    | Host system có thể lắng nghe events này bất cứ lúc nào.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['database'], // database | mail | fcm
        'notify_offline_only' => true,

        // Custom notification class (null = dùng built-in)
        // Nếu hệ thống đã có notification riêng, set class ở đây
        // Class phải nhận (ChatMessage, ChatRoom) trong constructor
        'notification_class' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Block & Report
    |--------------------------------------------------------------------------
    */
    'block_report' => [
        'block_enabled' => true,
        'report_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */
    'views' => [
        'enabled' => false,
        'publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Chat Roles
    |--------------------------------------------------------------------------
    |
    | These roles are seeded when running `php artisan chat:seed-roles`.
    | Roles define permissions WITHIN a chat room (not system-level).
    |
    */
    'default_roles' => [
        [
            'name' => 'owner',
            'display_name' => 'Owner',
            'permissions' => [
                'send_message',
                'delete_message',
                'add_member',
                'remove_member',
                'manage_room',
            ],
        ],
        [
            'name' => 'admin',
            'display_name' => 'Admin',
            'permissions' => [
                'send_message',
                'delete_message',
                'add_member',
                'remove_member',
            ],
        ],
        [
            'name' => 'member',
            'display_name' => 'Member',
            'permissions' => [
                'send_message',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize table names if needed.
    |
    */
    'table_names' => [
        'rooms' => 'chat_rooms',
        'roles' => 'chat_roles',
        'participants' => 'chat_participants',
        'messages' => 'chat_messages',
        'attachments' => 'chat_attachments',
        'blocked_users' => 'chat_blocked_users',
        'message_reports' => 'chat_message_reports',
    ],
];
