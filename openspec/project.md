# OpenSpec Project: VPoint Care / WACS

## Purpose

VPoint Care adalah aplikasi WhatsApp Customer Service untuk mengelola percakapan customer, balasan agent, auto-reply AI, ticketing, master data, dan integrasi WhatsApp melalui WAHA dalam satu panel admin berbasis Laravel Filament.

## Product Context

Aplikasi dipakai oleh tim operasional/customer service VPoint untuk:

- Menerima dan membaca pesan WhatsApp customer.
- Membalas pesan secara manual dari admin panel.
- Mengaktifkan bantuan AI untuk draft atau auto-reply.
- Membuat ticket dari percakapan customer.
- Mengelola data customer, instansi, nomor WhatsApp, grup, pengguna, role, permission, dan knowledge base.
- Memantau log integrasi, webhook, error, dan histori chat.

## Technology Stack

- PHP 8.3+
- Laravel 13
- Filament 5
- Microsoft SQL Server
- Laravel Reverb
- Laravel database queue
- WAHA WhatsApp gateway
- OpenAI / DeepSeek / OpenRouter sebagai provider AI
- Node.js, npm, Vite, Tailwind CSS

## Architecture

```text
WhatsApp Client
    ↓
WAHA Server
    ↓ webhook
Laravel Route /webhooks/waha/{token?}
    ↓
WahaWebhookProcessor
    ↓
SQL Server tables TChat, TChatD, logs, master mappings
    ↓ event / queue / service
Filament Admin Panel / Reverb / AI Agent / WahaSender
    ↓
WAHA sendText API
    ↓
WhatsApp Customer
```

## Core Domains

- Authentication and authorization through `MPengguna`, `MPeran`, `MHakAkses`, and Filament access helpers.
- WhatsApp inbox through `TChat`, `TChatD`, WAHA webhook, WAHA sender, and realtime event.
- AI auto-reply through `MPengaturanAi`, `MPengetahuan`, provider API keys, and working-hour/holiday rules.
- Ticketing through ticket transaction tables, assignment, status, priority, and attachments.
- Master data through customer, instansi, WhatsApp number, group, group member, holiday, knowledge, and user resources.
- Background processing through database queue and database-managed scheduler.

## Development Rules

- Preserve existing route contracts, especially `/admin`, `/webhooks/waha/{token?}`, `/admin/waha-media/{message}`, `/profile-storage/{path}`, and broadcast channel names.
- Preserve SQL Server compatibility. Migration logic must not assume MySQL/PostgreSQL syntax.
- Preserve `MPengguna` as the authentication user source.
- Preserve WAHA payload normalization for phone number, group id, `@c.us`, `@g.us`, and `@lid` cases.
- Do not store API keys, webhook tokens, or production passwords in source code.
- Any behavior change in AI auto-reply must consider working hours, holidays, excluded numbers, provider selection, and `KirimKeWaha` mode.
- Any UI menu/permission change must keep `AccessPermissions`, `NavigationHelper`, seeders, and Filament resource visibility aligned.

## Deployment Rules

- Production document root must point to `src/public`.
- Production must run web server, queue worker, scheduler, and Reverb as separate managed processes.
- `php artisan migrate --force` must be run only after database backup.
- `npm run build` must be run before publishing frontend changes.
- `php artisan optimize:clear` and `php artisan optimize` should be run after dependency/config updates.
- `.env` must be environment-specific and never committed.

## Spec Index

- `specs/vpoint-care/spec.md`: application capabilities and acceptance criteria.
