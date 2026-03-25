# Events & Notifications — phucbui/laravel-chat

> Documentation for 6 broadcast events, notification system, and channel naming conventions.

## Events Overview

All events are **ALWAYS fired** regardless of notification config. Host project can listen to these events in `EventServiceProvider`.

| Event | Channel | Broadcast As | When fired |
|---|---|---|---|
| `MessageSent` | `private: chat.room.{id}` | `message.sent` | New message sent |
| `MessageRead` | `private: chat.room.{id}` | `message.read` | Marked as read |
| `UserTyping` | `private: chat.room.{id}` | `user.typing` | Typing indicator |
| `RoomUpdated` | `private: chat.room.{id}` | `room.updated` | Room/participant changes |
| `UserOnlineStatusChanged` | `public: chat.online` | `user.online_status` | Online status change |
| `GenericBroadcast` | Dynamic | Dynamic | Used internally by ReverbDriver |

## Event Details

### MessageSent

```json
{
  "message": {
    "id": 42,
    "room_id": 1,
    "sender_type": "App\\Models\\User",
    "sender_id": 3,
    "type": "text",
    "body": "Hello!",
    "metadata": null,
    "parent_id": null,
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Properties:** `ChatMessage $message`, `ChatRoom $room`

### MessageRead

```json
{
  "room_id": 1,
  "actor_type": "App\\Models\\User",
  "actor_id": 3,
  "read_at": "2024-01-15T10:31:00.000000Z"
}
```

### UserTyping

```json
{
  "room_id": 1,
  "actor_type": "App\\Models\\User",
  "actor_id": 3,
  "is_typing": true
}
```

### RoomUpdated

```json
{
  "room_id": 1,
  "action": "participant_added",
  "name": "Team Chat",
  "max_members": 20
}
```

**Action values:** `updated`, `participant_added`, `participant_removed`

### UserOnlineStatusChanged

```json
{
  "actor_type": "App\\Models\\User",
  "actor_id": 3,
  "is_online": true
}
```

**Channel:** Public `chat.online` (no auth required)

---

## Notification System

### 3 Modes

```mermaid
graph TB
    MSG["New message"] --> CHECK{"notifications.enabled?"}
    CHECK -->|true| CLASS{"notification_class?"}
    CHECK -->|false| EVT["Only fires MessageSent event<br/>Host handles notifications"]
    CLASS -->|null| BUILTIN["NewMessageNotification<br/>(built-in)"]
    CLASS -->|custom| CUSTOM["Custom Notification Class<br/>(host project)"]
    BUILTIN & CUSTOM --> CHANNELS["Send via channels:<br/>database, mail, fcm..."]
```

### Config

```php
'notifications' => [
    'enabled' => true,                    // false = event-only mode
    'channels' => ['database'],           // database | mail | fcm
    'notify_offline_only' => true,
    'notification_class' => null,         // null = built-in, or custom class
],
```

### Built-in NewMessageNotification

**Payload (toArray):**
```json
{
  "type": "chat_message",
  "room_id": 1,
  "room_name": "Support Room",
  "message_id": 42,
  "message_body": "Hello!",
  "message_type": "text",
  "sender_type": "App\\Models\\User",
  "sender_id": 3
}
```

### Custom Notification Class

Class must accept `(ChatMessage $message, ChatRoom $room)` in constructor:

```php
// App\Notifications\ChatNotification
class ChatNotification extends Notification
{
    public function __construct(
        public ChatMessage $message,
        public ChatRoom $room,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm']; // custom channels
    }
}
```

Config:
```php
'notification_class' => \App\Notifications\ChatNotification::class,
```

### Event-Only Mode (Host handles notifications)

```php
// Host AppServiceProvider or EventServiceProvider:
Event::listen(MessageSent::class, function (MessageSent $event) {
    // Send notification using your own system
    $message = $event->message;
    $room = $event->room;
    // ... custom notification logic
});
```

## Channel Naming Convention

| Driver | Private Channel | Presence Channel |
|---|---|---|
| Reverb | `chat.room.{id}` | `presence-chat.room.{id}` |
| Socket.IO | `chat_room_{id}` | `presence_chat_room_{id}` |
| Pusher | `private-chat.room.{id}` | `presence-chat.room.{id}` |

Public channel: `chat.online` (all drivers)
