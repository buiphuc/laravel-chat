# PhucBui Laravel Chat

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phucbui/laravel-chat.svg?style=flat-square)](https://packagist.org/packages/phucbui/laravel-chat)
[![Total Downloads](https://img.shields.io/packagist/dt/phucbui/laravel-chat.svg?style=flat-square)](https://packagist.org/packages/phucbui/laravel-chat)
[![License](https://img.shields.io/packagist/l/phucbui/laravel-chat.svg?style=flat-square)](https://github.com/phucbui/laravel-chat/blob/main/LICENSE.md)

A next-generation, flexible real-time chat package tailored for Laravel. Designed with a **Multi-Actor System**, polymorphic participants, capability-based access control, and seamless real-time broadcasting integrations (Reverb, Socket.IO, Pusher).

Whether you need a simple 1v1 chat, a customer-support auto-routing module, or a robust multi-role group chat system, `phucbui/laravel-chat` gives you the ultimate foundation.

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration & Setup](#configuration--setup)
  - [1. Configure Actors](#1-configure-actors)
  - [2. Model Setup](#2-model-setup)
  - [3. Register Resolvers](#3-register-resolvers)
- [Key Concepts](#key-concepts)
- [Detailed API Usage](#detailed-api-usage)
- [Broadcasting](#broadcasting)
- [Testing](#testing)
- [License](#license)

---

## Features

- 🎭 **Actor-based Architecture**: Support multiple authentication guards and tables simultaneously (e.g. `admins`, `customers`, `users`).
- 🔗 **Polymorphic Participants**: Rooms can hold mixed actor types. Example: 2 Admins and 1 Customer in the same group.
- 🚦 **Capability-Driven Access Control**: Roles aren't hardcoded. Features (creating groups, blocking users, reading reports) are controlled via dynamic boolean flags in config.
- 📡 **Multi-Driver Realtime**: Out-of-the-box support for Laravel Reverb, Pusher, and standard Socket.IO.
- 🤖 **Auto-Routing**: Intelligently route incoming `client` chats to available `admins` based on flexible scheduling strategies (Least busy, Round-robin, Last contacted).
- 📎 **File Attachments**: Send documents and images seamlessly.
- 🛡️ **Block & Report**: Built-in moderation endpoints for banning users and flagging toxic messages.
- 🔍 **Full-Text Search**: Robust message search logic.

---

## Requirements

- **PHP**: `^8.2`
- **Laravel**: `^11.0` or `^12.0`
- **Database**: MySQL, PostgreSQL, or SQLite

---

## Installation

You can install the package via composer:

```bash
composer require phucbui/laravel-chat
```

Publish the package assets, configuration, and migrations:

```bash
php artisan chat:install
```

Run the database migrations:

```bash
php artisan migrate
```

Seed the default Chat Roles (`owner`, `admin`, `member`):

```bash
php artisan chat:seed-roles
```

---

## Documentation

Since `laravel-chat` is a highly customizable package with multi-actor routing, capabilities, and dynamic websockets, we provide comprehensive documentation in both **English** and **Vietnamese**:

### 🇬🇧 English Documentation
- 📖 **[Installation & Setup Guide](specs/en/installation-guide.md)**: Actor configuration, routing, drivers, and broadcasting.
- ⚙️ **[Configuration Reference](specs/en/config.md)**: Detailed explanation of `config/chat.php` with real-world sample cases (Slack clone, Customer Support system).
- 🔌 **[API Endpoints](specs/en/api-endpoints.md)**: Full REST API documentation with exact JSON payloads.
- 📐 **[Other Specifications](specs/README.md)**: Architecture, Models, Events and Services deep dive.

### 🇻🇳 Tài liệu Tiếng Việt
- 📖 **[Hướng dẫn Cài đặt & Thiết lập](specs/vi/installation-guide.md)**: Cấu hình Actor, phân quyền, websockets và cách tích hợp.
- ⚙️ **[Giải thích Cấu hình (Config)](specs/vi/config.md)**: Giải thích cấu hình `config/chat.php` kèm các file config mẫu cho thực tế (Slack clone, hệ thống CSKH đa nền tảng).
- 🔌 **[Tài liệu API Endpoints](specs/vi/api-endpoints.md)**: Chi tiết toàn bộ hệ thống giao tiếp API và cấu trúc JSON trả về.
- 📐 **[Tài liệu Khác](specs/README.md)**: Tương tác Event, Sơ đồ Class, Service,...

---

## Credits & Contact

Developed and maintained by **Bui Phuc**.
- **Email**: bui.phuc.vt@gmail.com
- **LinkedIn**: [https://www.linkedin.com/in/bmphuc/](https://www.linkedin.com/in/bmphuc/)

Feel free to reach out if you have any questions, feature requests, or collaboration proposals!

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
