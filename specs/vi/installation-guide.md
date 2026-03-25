# Hướng dẫn Cài đặt & Cấu hình

Tài liệu này sẽ hướng dẫn anh chi tiết cách setup, cấu hình các Actor, và ví dụ sử dụng API thực tế cho `phucbui/laravel-chat`.

## 1. Cấu hình & Thiết lập

## 1. Giải thích chi tiết file `config/chat.php`

Khi publish file cấu hình, anh sẽ thấy nhiều section. Dưới đây là ý nghĩa chi tiết:

### 1.1 `actors` (Định danh người gửi/nhận)
Trái tim của hệ thống. Thay vì "User", hệ thống coi mọi người là "Actor". Anh có thể có `admin`, `customer`, `vendor`...
- `model`: Class Model đại diện (vd: `App\Models\User`).
- `guard`: Auth guard dùng cho Actor này (`sanctum`, `web`...).
- `middleware`: Các middleware sẽ tự động được gán vào Route của Actor này.
- `route_prefix`: Khai báo prefix tự động sinh API, VD: `api/admin/chat` hoặc `api/customer/chat`.
- `capabilities`: Cấp quyền vĩ mô cho Actor bằng boolean:
  - `can_initiate_chat`: Được phép chủ động tạo phòng chat không.
  - `can_see_all_rooms`: (Dành cho super_admin) có thể xem cả phòng mình ko tham gia.
  - `can_create_group`: Quyền tạo Group cá nhân.
  - `can_receive_auto_routing`: Admin có được nằm trong danh sách xếp hàng nhận tin nhắn từ khách (Auto-routing) hay ko.
  - `can_block_users`, `can_review_reports`: Cấp quyền kiểm duyệt chống spam.

### 1.2 `chat_types` (Phân quyền giao tiếp)
- `allowed_pairs`: Khai báo các Actor nào được phép chat với nhau. 
  *Ví dụ: `['client', 'admin']` (Khách chat với Admin), `['admin', 'admin']` (Admin chat nội bộ).*
- `group`: boolean. Có cho phép chat nhóm nhiều người tham gia hay không.

### 1.3 `auto_routing` (Chia việc tự động)
Hệ thống tự động tìm Admin cho Khách hàng khi Khách tạo hội thoại lần đầu:
- `enabled`: Bật/tắt.
- `from_actor`: Actor bị route (thường là `client`).
- `to_actor`: Actor nhận route (thường là `admin`).
- `strategy`: Thuật toán chọn người `least_busy` (chọn admin đang ít chat nhất), `round_robin` (chia đều xoay vòng), `last_contacted` (chọn admin đã phục vụ khách đó lần trước).

### 1.4 Các tính năng phụ trợ
- `attachments`: Cấu hình upload file (disk `public`, Max size = 10MB, allowed_types `image/*`, `pdf`).
- `block_report`: Bật tắt tính năng Ban user và Báo cáo tin nhắn rác.
- `notifications`: Tự động đẩy Notification tới những ai đang offline để gửi Email/FCM Push (Có thể ghi đè custom notification class).
- `default_roles`: Các Role dùng ĐỂ CẤP QUYỀN TRONG Group Chat (owner, admin, member) kiểm soát giới hạn add/kick thành viên.

---

## 2. Hoàn thiện Sample Cases (Ví dụ thực tế)

### Sample 1: Hệ thống Chăm sóc Dịch vụ (CSKH)
**Nhu cầu**: Khách hàng `client` từ table `customers` chỉ được chat với nhân viên CSKH `admin`. Nhân viên CSKH thì được chat với Quản lý `super_admin`.

```php
'actors' => [
    'super_admin' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/super-admin/chat',
        'capabilities' => ['can_see_all_rooms' => true, 'can_review_reports' => true],
    ],
    'admin' => [
        'model' => App\Models\User::class, // Cùng model User nhưng khác Role
        'route_prefix' => 'api/admin/chat',
        'capabilities' => [
            'can_receive_auto_routing' => true, // Sẵn sàng nhận tin nhắn từ Khách
            'can_create_group' => true,
        ],
    ],
    'client' => [
        'model' => App\Models\Customer::class, // Khác model (Multi-table auth)
        'route_prefix' => 'api/chat',
        'capabilities' => [
            'can_create_group' => false, // Khách không được tạo group
            'can_initiate_chat' => true, // Nhưng được tạo direct chat 1-1 với Admin
            'can_receive_auto_routing' => false,
        ],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['client', 'admin'],
        ['admin', 'admin'],
        ['admin', 'super_admin'], // Client KHÔNG ĐƯỢC chat trực tiếp với super_admin
    ],
],
'auto_routing' => [
    'enabled' => true,
    'from_actor' => 'client',
    'to_actor' => 'admin',
    'strategy' => 'least_busy', // Chọn CSKH đang rảnh nhất
]
```

### Sample 2: Mạng xã hội nội bộ (Company Slack)
**Nhu cầu**: Toàn bộ nhân viên dùng chung 1 bảng `users`, ai cũng có thể tạo group chat và chat 1-1 với bất kỳ ai cực kỳ linh hoạt tự do.

```php
'actors' => [
    'employee' => [
        'model' => App\Models\User::class,
        'route_prefix' => 'api/chat',
        'capabilities' => [
            'can_initiate_chat' => true,
            'can_create_group' => true,
            'can_manage_participants' => true, // Được quyền tự add/kick ng khác ở room của họ
            'can_block_users' => true, 
        ],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['employee', 'employee'], // Cho phép chat nội bộ toàn công ty
    ],
    'group' => true, // Mở khoá tính năng tạo chat nhóm
],
'auto_routing' => [
    'enabled' => false, // Tắt, không dùng hệ thống phân line CSKH
]
```

### Sample 3: Nền tảng Đa đối tác (E-commerce / Booking Platform)
**Yêu cầu**: Nền tảng có 3 bên: `admin` (BQL Sàn), `client` (Shop/Nhà cung cấp), `customer` (Khách lẻ). Khách lẻ có thể nhắn cho Shop, hoặc nhắn khiếu nại cho BQL Sàn. Shop có thể chat với Khách, hoặc nhờ BQL Sàn hỗ trợ. Không dùng auto-routing vì khách tự chủ động chọn đích đến (VD: Bấm nút "Chat với Shop").

```php
'actors' => [
    'admin' => [
        'model' => App\Models\User::class, // Nhân viên Sàn
        'route_prefix' => 'api/admin/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => true, 'can_block_users' => true],
    ],
    'client' => [
        'model' => App\Models\Vendor::class, // Bảng các Shop
        'route_prefix' => 'api/vendor/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => false],
    ],
    'customer' => [
        'model' => App\Models\Customer::class, // Bảng Khách lẻ
        'route_prefix' => 'api/customer/chat',
        'capabilities' => ['can_initiate_chat' => true, 'can_create_group' => false],
    ]
],
'chat_types' => [
    'allowed_pairs' => [
        ['customer', 'client'], // Khách chat với Shop
        ['customer', 'admin'],  // Khách khiếu nại lên Sàn
        ['client', 'admin'],    // Shop nhờ Sàn hỗ trợ
        ['admin', 'admin'],     // Sàn nội bộ
    ],
    'group' => true, // Ví dụ: Tạo group có Khách, Shop và Admin để giải quyết tranh chấp 3 bên
],
'auto_routing' => [
    'enabled' => false, // Khách tự click "Chat với Shop X", hoặc "Chat với Admin Y"
]
```

---

## 3. Thiết lập Model & Register Resolvers

Bất kỳ Eloquent Model nào tham gia chat đều phải implement `ChatActorInterface` và sử dụng trait `HasChat`.

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

### Đăng ký Resolvers

Để hệ thống routing biết chính xác *ai* đang gửi request, hãy đăng ký logic khớp actor trong `AppServiceProvider`:

```php
use PhucBui\Chat\ChatFacade as Chat;

public function boot(): void
{
    // Actor 'admin' là những ai có role 'admin'
    Chat::matchActorUsing('admin', function ($user) {
        return $user->hasRole('admin');
    });

    // Actor 'client'
    Chat::matchActorUsing('client', function ($user) {
        return $user->hasRole('member');
    });

    // Tuỳ chọn: Phân tích Avatar và Tên hiển thị
    Chat::resolveDisplayNameUsing(fn($user) => $user->name ?? 'Unknown');
    Chat::resolveAvatarUsing(fn($user) => $user->avatar_url ?? null);
}
```

---

## 2. Ví dụ API Cơ bản

Bên dưới là các payload giao tiếp. Ví dụ ta đang là `client` có prefix `/api/chat`.

### Tạo Room cá nhân (1v1)
**Endpoint:** `POST /api/chat/rooms`

**Payload:**
```json
{
    "type": "direct",
    "participant_type": "App\\Models\\User",
    "participant_id": 2
}
```

### Tạo Group Room
Yêu cầu actor phải có capability `can_create_group`.

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

### Gửi Tin nhắn
Gửi text vào room

**Endpoint:** `POST /api/chat/rooms/{room_id}/messages`

**Payload:**
```json
{
    "type": "text",
    "body": "Hello team, let's review the deployment plan."
}
```

### Cập nhật Trạng thái Đã đọc
Khi người dùng mở thanh chat, gọi API này để update con trỏ `last_read_at`.

**Endpoint:** `POST /api/chat/rooms/{room_id}/read`
*(Không cần payload)*

### Chặn User (Block)
Chặn người lạ không cho phép họ nhắn tin vào direct room cùng mình nữa.

**Endpoint:** `POST /api/chat/users/{actor_id}/block`

**Payload:**
```json
{
    "target_type": "App\\Models\\User",
    "reason": "Spamming inappropriate links"
}
```

---

## 3. WebSockets / Broadcasting

`laravel-chat` tự động fire events. Đảm bảo file `.env` Laravel config đúng:

```env
BROADCAST_CONNECTION=reverb

# Hoặc nếu xài Pusher
PUSHER_APP_ID=your_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

**Các Channel Frontend nên lắng nghe:**

1. **User Channel** (Dùng để nhận thư mời vào group, hoặc auto-routing message)
   - Channel: `chat.user.{actor_type}.{actor_id}`
   - *Example:* `chat.user.App.Models.User.1`

2. **Room Channel** (Nhận tin nhắn mới, read receipts, ai đang gõ chữ)
   - Channel: `chat.room.{room_id}`
   - *Example:* `chat.room.5`

**Các Events sẽ được ném lên Frontend:**
- `MessageSent`
- `MessageRead`
- `UserTyping`
- `RoomCreated`
