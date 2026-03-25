# Architecture — phucbui/laravel-chat

> Kiến trúc tổng thể của package chat, bao gồm Actor System, Driver Strategy Pattern, Repository Pattern, Capability-based ACL, và Dynamic Routes.

## Tổng quan kiến trúc

```mermaid
graph TB
    subgraph "HTTP Layer"
        ROUTES["Dynamic Routes<br/>(per Actor)"]
        MW_RESOLVE["ResolveActorMiddleware"]
        MW_CAP["CheckCapabilityMiddleware"]
        MW_ROOM["ChatRoomAccessMiddleware"]
        CTRL_ROOM["RoomController"]
        CTRL_MSG["MessageController"]
        CTRL_PART["ParticipantController"]
        CTRL_BLOCK["BlockController"]
        CTRL_REPORT["ReportController"]
        RES["API Resources<br/>(Room, Message, Participant)"]
    end

    subgraph "Service Layer"
        SVC_CHAT["ChatService"]
        SVC_ROOM["RoomService"]
        SVC_MSG["MessageService"]
        SVC_ROUTING["AdminRoutingService"]
        SVC_ATTACH["AttachmentService"]
        SVC_BLOCK["BlockService"]
        SVC_SEARCH["SearchService"]
        DTO["DTOs<br/>(RoomData, MessageData, ParticipantData)"]
    end

    subgraph "Data Layer (Repository Pattern)"
        REPO_IF["7 Repository Interfaces"]
        REPO_IMPL["8 Repository Implementations<br/>(Base + 7 Concrete)"]
        MODELS["7 Eloquent Models<br/>(ChatRoom, ChatRole, ChatParticipant,<br/>ChatMessage, ChatAttachment,<br/>ChatBlockedUser, ChatMessageReport)"]
    end

    subgraph "Infrastructure"
        DRV_IF["SocketDriverInterface"]
        DRV_REVERB["ReverbDriver"]
        DRV_SOCKETIO["SocketIoDriver"]
        DRV_PUSHER["PusherDriver"]
        EVENTS["6 Events<br/>(MessageSent, MessageRead,<br/>UserTyping, RoomUpdated,<br/>UserOnlineStatusChanged,<br/>GenericBroadcast)"]
        NOTIF["NewMessageNotification"]
    end

    subgraph "Core"
        SP["ChatServiceProvider"]
        MGR["ChatManager"]
        FACADE["Chat Facade"]
    end

    ROUTES --> MW_RESOLVE --> MW_CAP
    MW_CAP --> CTRL_ROOM & CTRL_MSG & CTRL_PART & CTRL_BLOCK & CTRL_REPORT
    CTRL_ROOM & CTRL_MSG --> MW_ROOM
    CTRL_ROOM --> SVC_ROOM
    CTRL_MSG --> SVC_MSG
    CTRL_ROOM --> SVC_ROUTING
    CTRL_MSG --> SVC_ATTACH
    CTRL_BLOCK --> SVC_BLOCK

    SVC_CHAT --> SVC_ROOM & SVC_MSG
    SVC_ROOM & SVC_MSG & SVC_BLOCK --> REPO_IF --> REPO_IMPL --> MODELS
    SVC_MSG --> DRV_IF
    DRV_IF --> DRV_REVERB & DRV_SOCKETIO & DRV_PUSHER
    SVC_MSG --> EVENTS
    SVC_MSG --> NOTIF

    SP --> MGR
    FACADE --> MGR
```

## Design Patterns

### 1. Actor System

Thay thế hard-code admin/client bằng hệ thống actors config-driven:

```mermaid
graph LR
    CONFIG["config/chat.php<br/>actors: super_admin, admin, client, ..."] --> MGR["ChatManager"]
    MGR --> RESOLVE["resolveActor(actorName)"]
    MGR --> MATCH["isActorType(actorName, user)"]
    MGR --> DETECT["detectActorType(user)"]
    MGR --> CAP["hasCapability(actorName, cap)"]
```

- Mỗi actor có: `model`, `guard`, `middleware`, `route_prefix`, `capabilities`
- Hỗ trợ **multi-table auth** (mỗi actor dùng bảng khác nhau) và **single-table + roles**
- Actor resolvers/matchers được đăng ký runtime qua `Chat::resolveActorUsing()`, `Chat::matchActorUsing()`

### 2. Polymorphic Participants

```mermaid
graph LR
    USER["App\\Models\\User<br/>(id: 1)"] --> PART_A["ChatParticipant<br/>actor_type: App\\Models\\User<br/>actor_id: 1"]
    CUSTOMER["App\\Models\\Customer<br/>(id: 5)"] --> PART_B["ChatParticipant<br/>actor_type: App\\Models\\Customer<br/>actor_id: 5"]
    PART_A --> ROOM["ChatRoom (id: 10)"]
    PART_B --> ROOM
```

- `chat_participants` dùng morph (`actor_type` + `actor_id`)
- Một room có thể chứa participants từ nhiều model/bảng khác nhau
- Tương tự cho `sender_type/sender_id` trong messages, `blocker/blocked` trong blocks

### 3. Capability-Based Access Control

Không hard-code quyền, mà dùng capabilities config:

| Capability | Mô tả |
|---|---|
| `can_initiate_chat` | Có thể bắt đầu cuộc chat mới |
| `can_see_all_rooms` | Xem tất cả rooms (super_admin) |
| `can_create_group` | Tạo group chat |
| `can_manage_participants` | Thêm/xóa participants |
| `can_change_roles` | Đổi role participant (chỉ super_admin) |
| `can_receive_auto_routing` | Nhận client auto-route |
| `can_review_reports` | Xem và xử lý reports |
| `can_block_users` | Block users |
| `can_search_messages` | Tìm kiếm messages |

### 4. Socket Driver Strategy

```mermaid
graph TB
    IF["SocketDriverInterface"] --> R["ReverbDriver<br/>(Laravel Broadcasting)"]
    IF --> S["SocketIoDriver<br/>(HTTP → Node.js server)"]
    IF --> P["PusherDriver<br/>(Pusher SDK)"]
    CONFIG["config: chat.driver"] -.-> IF
```

Driver được resolve trong ServiceProvider dựa trên `config('chat.driver')`.

### 5. Dynamic Routes

Routes được sinh tự động cho mỗi actor:

```
config('chat.actors.super_admin.route_prefix') → api/super-admin/chat/*
config('chat.actors.admin.route_prefix')       → api/admin/chat/*
config('chat.actors.client.route_prefix')      → api/chat/*
```

Mỗi actor set có cùng endpoints nhưng middleware stack khác nhau (guard, resolve_actor).

### 6. Auto-Routing

Khi client gửi tin nhắn, package tự động tìm admin phù hợp:

```mermaid
graph TB
    CLIENT["Client gửi tin nhắn"] --> CHECK{"Auto-routing enabled?"}
    CHECK -->|Yes| STRATEGY{"Strategy?"}
    CHECK -->|No| MANUAL["Client chọn target"]
    STRATEGY --> LC["last_contacted<br/>Admin đã chat gần nhất"]
    STRATEGY --> LB["least_busy<br/>Admin ít room nhất"]
    STRATEGY --> RR["round_robin<br/>Luân phiên"]
    LC & LB & RR --> FALLBACK{"Tìm được?"}
    FALLBACK -->|Yes| ROOM["Tạo/mở direct room"]
    FALLBACK -->|No| FB["Fallback strategy"]
```

## Data Flow

```
Request → Middleware (Auth → ResolveActor → CheckCapability)
        → Controller (validate request)
        → Service (business logic)
        → Repository (data access)
        → Model (Eloquent ORM)
        → Database

Response ← Resource (format JSON)
         ← Controller
         ← Event (broadcast to socket)
         ← Notification (offline users)
```
