# Change: Tambah Login dan Register User via Google dan SSO

## Summary

Tambahkan menu/fitur autentikasi untuk **login** dan **register user baru** menggunakan akun Google dan provider SSO perusahaan, tetap memakai `MPengguna` sebagai sumber user internal serta role/permission existing untuk akses menu Filament.

## Goals

- Menyediakan tombol **Login dengan Google** pada halaman login admin.
- Menyediakan tombol **Login dengan SSO** pada halaman login admin bila konfigurasi SSO aktif.
- Menyediakan flow **register user baru** dari Google dan SSO dengan status aman sebelum user mendapatkan akses penuh.
- Menghubungkan akun eksternal ke data internal `MPengguna` tanpa mengganti model auth utama.
- Menjaga role, permission, menu, dan status aktif user tetap dikontrol oleh data internal aplikasi.
- Menyediakan audit/log untuk proses login, register, callback, link akun, dan kegagalan OAuth/SSO.

## Non-Goals

- Tidak mengganti login username/password existing.
- Tidak mengganti tabel auth utama ke tabel `users` default Laravel.
- Tidak memberi akses otomatis ke menu admin sebelum role, permission, dan status user valid.
- Tidak menyimpan access token atau refresh token provider sebagai plaintext.
- Tidak mengubah kontrak route WAHA, inbox, AI Agent, atau ticketing.

## User Stories

- Sebagai user existing, saya ingin login memakai Google agar tidak perlu mengetik password aplikasi.
- Sebagai user existing, saya ingin login memakai SSO perusahaan agar akses mengikuti identitas organisasi.
- Sebagai user baru, saya ingin register memakai Google/SSO agar profil awal dapat dibuat cepat.
- Sebagai admin, saya ingin user baru masuk status pending agar dapat diverifikasi sebelum mendapat akses.
- Sebagai admin, saya ingin melihat identitas provider yang terhubung agar dapat audit sumber login user.

## Scope

### In Scope

- UI tombol login/register Google dan SSO pada halaman auth.
- OAuth Google callback dan SSO callback.
- Mapping identitas eksternal ke `MPengguna` berdasarkan email dan provider subject/id.
- Pembuatan user pending untuk registrasi baru.
- Konfigurasi provider melalui `.env` dan config Laravel.
- Permission/menu admin untuk review user pending bila belum tersedia.
- Audit log autentikasi eksternal.
- Dokumentasi setup provider Google dan SSO.

### Out of Scope

- Provisioning otomatis role dari Google Workspace/IdP group kecuali disetujui terpisah.
- Multi-tenant domain mapping lanjutan.
- Passwordless email magic link.
- Mobile app auth flow.

## Impacted Areas

- `src/app/Models/MPengguna.php`
- Auth provider `pengguna` dan guard Filament admin.
- Halaman login/register Filament.
- Route OAuth callback.
- Migration SQL Server untuk field/link identitas eksternal bila belum ada.
- Seeder permission/menu untuk review registrasi pending.
- Dokumentasi `.env` dan setup provider.

## Decisions

- SSO default SHALL use OIDC/OAuth2. SAML MAY be added only when the selected company IdP requires it.
- External registration SHALL be enabled only with pending approval; new users SHALL NOT enter the admin panel automatically.
- Google and SSO emails SHALL be restricted by allowed-domain configuration.
- New users SHALL receive no role until an administrator approves and assigns one.
- Auto-link by email SHALL be allowed only for verified emails, active internal users, allowed domains, and non-conflicting provider subjects.
- Provider access/refresh tokens SHALL NOT be stored by default.
- Login UI SHALL be polished, responsive, and explicit about secure company-domain verification.
