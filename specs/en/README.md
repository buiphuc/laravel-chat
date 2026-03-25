# phucbui/laravel-chat — Specifications

> Complete technical documentation for the `phucbui/laravel-chat` package — a flexible realtime chat system for Laravel.

## Table of Contents

| File | Content |
|---|---|
| [architecture.md](./architecture.md) | Overall architecture, Actor System, Driver Strategy, data flow |
| [api-endpoints.md](./api-endpoints.md) | API reference: endpoints, request/response, middleware |
| [models.md](./models.md) | Database tables, Eloquent models, polymorphic relationships |
| [services.md](./services.md) | Service layer, Repository Pattern, DTOs, AdminRouting |
| [events.md](./events.md) | Events, Broadcasting, Notifications |
| [config.md](./config.md) | Full configuration reference |
| [integration-guide.md](./integration-guide.md) | Integration guide for host projects |

## Quick Overview

- **Package**: `phucbui/laravel-chat`
- **Laravel**: >= 11.x
- **PHP**: >= 8.2
- **Patterns**: Repository, DTO, Strategy (Socket Drivers), Capability-based ACL, Polymorphic Morph
- **Drivers**: Reverb, Socket.IO, Pusher
- **Total files**: 71
