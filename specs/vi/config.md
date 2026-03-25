# Configuration Reference — phucbui/laravel-chat

> Tham chiếu và Giải thích toàn bộ các cấu hình có trong file `config/chat.php`. Bảng cấu hình này điều khiển mọi hành vi vĩ mô của hệ thống Chat, từ tự động chia việc, phân quyền cho đến Socket Server.

---

## 1. `actors` (Định danh Nhóm Người Dùng)

Đây là khai báo quan trọng nhất. Nó định nghĩa CÁC NHÓM NGƯỜI có thể tham gia vào chat. Hệ thống hỗ trợ hoàn hảo Multi-table (nhiều bảng như `users`, `admins`, `customers`) hoặc Single-table (1 bảng `users` dùng chung có Role).

### Các khoá cấu hình cho 1 Actor:
- `model` (string): Đường dẫn Full Class Model Eloquent đại diện cho nhóm người này. VD: `App\Models\Admin`.
- `guard` (string): Tên Guard Auth dùng để xác thực các API. VD: `sanctum`, `admin`, `web`.
- `middleware` (array): Các middleware mà route API của Actor này sẽ đi qua. VD: `['auth:sanctum']`.
- `route_prefix` (string): Hệ thống sẽ **tự động generate ra API endpoints** cho nhóm người này dưới tên miền prefix này. VD: `api/admin/chat`.
- `is_match` (null): *Không khả dụng ở file config*. Dùng runtime bằng lệnh `Chat::matchActorUsing()` ở AppServiceProvider để bind dữ kiện.

### `capabilities` (Các Khả năng Vĩ mô):
Phân quyền chức năng chung của nhóm Actor này:
- `can_initiate_chat`: Cho phép thao tác ấn bắt đầu chat 1-1 với người khác.
- `can_see_all_rooms`: Quyền Giám sát (Super Admin), có thể vào xem log chat của phòng người khác dù mình không ở trong phòng.
- `can_create_group`: Có quyền bấm nút "Tạo Nhóm Mới" và mời mọi người tham gia.
- `can_manage_participants`: Có quyền xoá thành viên/thêm thành viên trong nhóm đang chat (với điều kiện role trong phòng là Owner/Admin).
- `can_receive_auto_routing`: Người này **CÓ CHỨC NĂNG HỖ TRỢ KHÁCH** (VD: agent CSKH). Nếu `true`, họ sẽ lọt vào danh sách xếp hàng khi có khách nhắn tin vào.
- `can_block_users`: Bật chức năng Chặn User (chặn họ không cho spam tin nhắn direct nữa).
- `can_review_reports`: Được truy cập hệ thống theo dõi Report/Khiếu nại spam đoạn chat.
- `can_search_messages`: Mở khoá chức năng API tìm kiếm tin nhắn toàn cục.

---

## 2. `chat_types` (Phân quyền Giao tiếp)

Quy định các lằn ranh giới giữa các Nhóm người dùng:
- `allowed_pairs` (array): Mảng quy định **Actor A có được chat với Actor B hay không**. 
  - Khai báo dưới dạng `[['client', 'admin'], ['admin', 'admin']]`.
  - Giới hạn thiết thực: Ví dụ bạn KHÔNG muốn khách hàng `client` được phép tự tìm ID và gửi tin cho một `client` khác thì đừng khai báo pair `['client', 'client']`.
- `group` (bool): Biến tổng (Global toggle). Bật hoặc Tắt toàn bộ chức năng Chat Nhóm cho ứng dụng.

---

## 3. `auto_routing` (Phân luồng Call Center Tự động)

Hệ thống Call Center CSKH nội bộ. Khi một luồng `from_actor` gửi 1 tin nhắn trực tiếp hệ thống mở màn (mà chưa biết gửi cho ai), hệ thống sẽ tự quét CSDL tìm 1 người thuộc nhóm `to_actor` đang online / phù hợp nhất để map 1-1 phòng chat cho 2 bên.

- `enabled` (bool): Bật/Tắt module này.
- `from_actor` (string): Nhóm người tìm sự trợ giúp (VD: `client`).
- `to_actor` (string): Nhóm người trực trả lời (VD: `admin`).
- `strategy` (string): Thuật toán chọn người CSKH phù hợp khi có tin nhắn từ khách:
  - `least_busy`: Tìm nhân viên `admin` đang có ít đoạn chat hoạt động trong ngày nhất. Tránh nhồi 1 nhân viên quá nhiều việc.
  - `round_robin`: Chia đều việc tuần tự vắt vòng.
  - `last_contacted`: Ưu tiên khách quen, tìm lại nhân viên đã chat với người khách này ở lần gần nhất (nếu còn hoạt động).
- `fallback` (string): Nếu thuật toán chính fail (VD nhân viên cũ đã nghỉ làm), chuyển sang thuật toán quay lui (VD mặc định: `least_busy`).

---

## 4. Băng chuyền Tin nhắn (Socket & DB)

### `driver` & `drivers` (Realtime Socket)
- `driver` (string): Kết nối Realtime mặc định (Nhận `.env`). Lựa chọn: `reverb`, `pusher`, `socketio`.
- `drivers`: Cấu hình cụ thể Connection Array cho từng socket server. Khai báo port, host, api keys cho Pusher, Socket.IO hoặc Laravel Reverb.

### `messages` (Giới hạn Hiển thị văn bản)
- `per_page` (int): Số lượng tin nhắn load thêm khi cuộn Paginate API. Định mức tối ưu DB.
- `max_length` (int): Chặn chửi thề độ dài, Số ký tự tối đa của một tin nhắn văn bản.
- `search_enabled` (bool): Global toggle Bật/Tắt search Full Text trên Frontend.

---

## 5. `notifications` (Đẩy Push Thông báo Offline)

Giao thức Push logic thông minh. Nếu bạn chat và đối phương **ĐANG TẮT TRÌNH DUYỆT / KHÔNG ONLINE** qua socket, hệ thống sẽ đẩy Laravel Notification (Email hoặc Firebase Push Noti). Cấu hình:

- `enabled` (bool): Bật thông báo rẽ nhánh. Nếu `false`, hệ thống chỉ đơn thuần đẩy Dispatch Event `MessageSent` và tuỳ cho Developer xử lý.
- `channels` (array): Các channel đẩy đi. Ví dụ `['database', 'mail', 'fcm']` (FCM dành cho mobile notification).
- `notify_offline_only` (bool): `true` = Chỉ đẩy Push Notification khi user thực sự đã tắt socket (Chống spam nháy chớp Notification nếu người ta đang chat).
- `notification_class`: Nếu bạn muốn ghi đè hoàn toàn Giao Diện Mail / Nội dung Notification Push, nhúng Class Custom của ứng dụng bạn vào đây. Nếu bỏ trống, dùng Class Mặc định của package.

---

## 6. `attachments` (File & Ảnh đính kèm)

- `enabled` (bool): Bật chức năng gửi upload attachment file.
- `disk` (string): Disk Storage (Mặc định `public`).
- `max_size` (int): Byte / ngàn (10240 = 10MB tối đa).
- `allowed_types` (array): Bộ lọc file. Mặc định `['image/*', 'application/pdf', 'text/*']`.
- `path` (string): Subfolder riêng biệt để chứa toàn bộ hình ảnh chat. 

---

## 7. Các cấu hình Hệ Thống cơ bản

### `block_report`
Bật Module Kiểm Soát Tiêu Cực (Moderation).
- `block_enabled`: Mở API Chặn người dùng.
- `report_enabled`: Mở API Báo cáo nội dung xấu lên Admin.

### `default_roles`
Khai báo tên chức vụ **BÊN TRONG 1 PHÒNG CHAT GROUP**. 
- Dữ liệu này dùng để gán khi `Seed` mặc định vào DB. Nó phân chia Permissions của phòng đi kèm như `owner` thì có thể phá phòng `manage_room`, còn `member` bình thường chỉ có `send_message`. (Đây là Role nội bộ của Room, Không phải Auth Role hệ thống Spatie).

### `table_names`
- Custom lại toàn bộ 7 Prefix tên Table gốc của CSDL (phòng trường hợp DB cũ bị trùng tên với Package). Bạn có quyền rename tuỳ ý các bảng như `chat_rooms`, `chat_roles`, `chat_messages` thành bất kì tên nào bạn thích, Package vẫn hoạt động hoàn hảo!

---

## 8. Các Mẫu Cấu hình Thực tế (Sample Configs)

### Sample 1: Hệ thống Chăm sóc Khách hàng (CSKH)
**Yêu cầu**: Khách (`client`) chỉ chat với CSKH (`admin`). CSKH sẽ được phân việc bởi hệ thống Auto-Routing khi khách bắt đầu chat.

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

### Sample 2: Mạng xã hội nội bộ (Company Slack)
**Yêu cầu**: Tất cả nhân sự xài chung bảng `users`, có thể tuỳ ý tạo nhóm chat và chat 1-1 với bất kỳ ai mà không bị giới hạn tuyến hỗ trợ. Tắt module Auto Routing.

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
