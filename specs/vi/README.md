# phucbui/laravel-chat — Specifications

> Tài liệu kỹ thuật chi tiết cho package `phucbui/laravel-chat` — hệ thống chat realtime linh hoạt cho Laravel.

## Mục lục

| File | Nội dung |
|---|---|
| [architecture.md](./architecture.md) | Kiến trúc tổng thể, Actor System, Driver Strategy, data flow |
| [api-endpoints.md](./api-endpoints.md) | API reference: endpoints, request/response, middleware |
| [models.md](./models.md) | Database tables, Eloquent models, polymorphic relationships |
| [services.md](./services.md) | Service layer, Repository Pattern, DTOs, AdminRouting |
| [events.md](./events.md) | Events, Broadcasting, Notifications |
| [config.md](./config.md) | Tham chiếu toàn bộ cấu hình |
| [integration-guide.md](./integration-guide.md) | Hướng dẫn tích hợp vào host project |

## Quick Overview

- **Package**: `phucbui/laravel-chat`
- **Laravel**: >= 11.x
- **PHP**: >= 8.2
- **Patterns**: Repository, DTO, Strategy (Socket Drivers), Capability-based ACL, Polymorphic Morph
- **Drivers**: Reverb, Socket.IO, Pusher
- **Tổng files**: 71
