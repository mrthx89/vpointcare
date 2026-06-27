# Plan Implementasi: Login dan Register User via Google dan SSO

## Tujuan

Membangun login dan registrasi user baru melalui Google dan SSO untuk panel admin VPoint Care, tanpa mengganti auth utama aplikasi yang memakai `MPengguna`, role, dan permission internal.

## Prinsip Desain

- `MPengguna` tetap menjadi sumber user internal.
- Login password existing tetap berjalan.
- User baru dari Google/SSO masuk status pending/inactive sampai admin approve.
- Role dan menu tetap dikontrol oleh `MPeran`, `MHakAkses`, `AccessPermissions`, dan `NavigationHelper`.
- Secret provider hanya berasal dari `.env`.
- Token, client secret, dan claim sensitif tidak boleh masuk log.

## Keputusan Default yang Dipakai

| Area | Pilihan | Rekomendasi |
| --- | --- | --- |
| Tipe SSO | OIDC/OAuth2, SAML, Azure AD, Keycloak | OIDC/OAuth2 sebagai default paling aman dan modern; SAML hanya jika provider mewajibkan |
| Registrasi user baru | Aktif / nonaktif | Aktif, tetapi semua user baru wajib pending approval |
| Domain email | Bebas / whitelist | Wajib whitelist domain perusahaan; email umum ditolak kecuali admin aktifkan eksplisit |
| Role default | Tanpa role / role pending | Tanpa role sampai admin approve dan assign role |
| Auto-link by email | Ya / tidak | Ya, hanya jika email verified, domain valid, user aktif, dan tidak ada konflik provider subject |


## Rekomendasi Final untuk Implementasi

- Gunakan **Google OAuth/OIDC** dan **SSO OIDC** sebagai standar utama karena mendukung `state`, `nonce`, issuer validation, audience validation, token expiry, dan claim verification yang jelas.
- Aktifkan register eksternal, tetapi hasilnya **tidak langsung login**; user baru masuk daftar pending dan admin harus approve.
- Wajibkan whitelist domain email perusahaan untuk Google dan SSO.
- Jangan memberi role default ke user baru; admin harus memilih role agar tidak terjadi privilege escalation.
- Auto-link akun external ke `MPengguna` existing hanya boleh jika email sudah verified dan user internal berstatus aktif.
- Simpan token provider hanya jika benar-benar diperlukan; default terbaik adalah **tidak menyimpan access token/refresh token**.
- Tambahkan audit log untuk semua percobaan login/register external, tetapi selalu sensor token, secret, dan claim sensitif.

## Rekomendasi UI/UX Login

- Buat halaman login dengan card modern: logo VPoint Care, judul singkat, subtitle keamanan, dan layout center responsif.
- Tombol utama: **Masuk dengan Google** memakai ikon Google, warna netral putih, border halus, hover state jelas.
- Tombol kedua: **Masuk dengan SSO Perusahaan** memakai ikon shield/building, warna primary brand, dan teks yang mudah dipahami user awam.
- Pisahkan tombol social login dan form password dengan divider `atau masuk dengan akun aplikasi`.
- Tampilkan badge kecil **Aman dengan verifikasi domain perusahaan** agar user paham alasan login external dibatasi.
- Untuk user baru, tampilkan halaman pending yang ramah: `Pendaftaran berhasil dikirim. Admin perlu menyetujui akun Anda sebelum dapat mengakses dashboard.`
- Untuk error, gunakan pesan aman dan sederhana: `Akun belum terdaftar atau belum disetujui. Hubungi administrator.` Jangan tampilkan detail teknis callback/token.
- Untuk admin, tambahkan filter **Pending Approval** pada menu pengguna dan aksi cepat **Approve**, **Reject**, **Assign Role**.

## Rekomendasi Security Wajib

- Wajib HTTPS pada semua redirect URL production.
- Wajib validasi OAuth `state` dan OIDC `nonce`.
- Wajib validasi issuer, audience/client id, signature, expiry, dan required claims.
- Wajib email verified sebelum auto-link atau register.
- Wajib domain whitelist sebelum create/link user.
- Wajib unique constraint pada `provider + provider_user_id`.
- Wajib tolak login jika user internal inactive, pending, rejected, atau tidak punya role.
- Wajib regenerasi session setelah login sukses.
- Wajib rate limit pada endpoint callback dan login initiation.
- Wajib audit log tanpa menyimpan token, client secret, authorization code, atau full ID token.
## Arsitektur Flow

```text
Guest /admin
  ↓
Login Page Filament
  ↓ tombol Google / SSO
OAuth/OIDC Redirect
  ↓
Provider Login
  ↓ callback
ExternalAuthService
  ↓ validasi state, nonce, email, domain, subject
Cari external identity link
  ↓ jika tidak ada
Cari MPengguna by verified email
  ↓ jika tidak ada dan register enabled
Create MPengguna pending + provider link
  ↓
Login aktif atau tampilkan pending approval
```

## Komponen yang Dibuat atau Diubah

### Backend

- Route redirect dan callback untuk Google.
- Route redirect dan callback untuk SSO.
- Service `ExternalAuthService` untuk validasi dan mapping profil.
- Model/tabel external identity link bila belum tersedia.
- Logic registrasi pending user.
- Audit log external auth.
- Validasi status user sebelum masuk panel.

### UI

- Tombol **Login dengan Google** di halaman login admin.
- Tombol **Login dengan SSO** di halaman login admin.
- Pesan sukses registrasi pending.
- Pesan gagal aman untuk callback error, domain ditolak, dan user inactive.
- Filter/aksi admin untuk approve user pending bila belum tersedia.

### Konfigurasi

Contoh variabel yang perlu disiapkan:

```env
GOOGLE_AUTH_ENABLED=true
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://domain.test/auth/google/callback
GOOGLE_ALLOWED_DOMAINS=vpoint.co.id

SSO_AUTH_ENABLED=false
SSO_PROVIDER=oidc
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_ISSUER_URL=
SSO_REDIRECT_URI=https://domain.test/auth/sso/callback
SSO_ALLOWED_DOMAINS=vpoint.co.id

EXTERNAL_REGISTRATION_ENABLED=true
EXTERNAL_REGISTRATION_DEFAULT_STATUS=pending
```

## Tahapan Implementasi

### 1. Analisis Auth Existing

- Review guard dan provider `pengguna`.
- Review `MPengguna` untuk field email, nama, password, status aktif, dan relasi role.
- Review halaman login/register Filament existing.
- Review resource pengguna untuk approval user.

### 2. Data Model

- Tambahkan tabel `pengguna_external_identities` atau nama yang mengikuti konvensi project.
- Field minimal: `id`, `pengguna_id`, `provider`, `provider_user_id`, `email`, `name`, `avatar_url`, `linked_at`, `last_login_at`, `metadata`, timestamps.
- Tambahkan unique index pada `provider + provider_user_id`.
- Tambahkan index pada `provider + email`.
- Pastikan migration memakai syntax aman untuk SQL Server.

### 3. Google OAuth

- Install/aktifkan library OAuth yang sesuai Laravel/Filament.
- Buat redirect ke Google dengan scope minimal `openid email profile`.
- Validasi callback, state, email verified, dan domain.
- Mapping profil ke `MPengguna` atau pending registration.

### 4. SSO

- Pilih implementasi sesuai provider final.
- Untuk OIDC, validasi issuer, audience, nonce, expiry, dan signature token.
- Untuk SAML, validasi signature, ACS URL, entity ID, dan required attributes.
- Normalisasi claim ke format internal: `subject`, `email`, `email_verified`, `name`, `provider`.

### 5. Login/Register Decision

Urutan keputusan callback:

1. Tolak jika provider disabled.
2. Tolak jika state/nonce/callback invalid.
3. Tolak jika email tidak verified atau domain tidak diizinkan.
4. Login jika provider subject sudah linked ke user aktif.
5. Link ke user aktif existing jika email sama dan tidak konflik.
6. Buat user pending jika registrasi aktif.
7. Tolak dengan instruksi hubungi admin jika registrasi nonaktif.

### 6. Approval Admin

- Tampilkan user pending di resource pengguna.
- Admin assign role dan ubah status menjadi aktif.
- Setelah approved, user bisa login Google/SSO.
- Rejected user tetap tidak bisa masuk panel.

### 7. Security Checklist

- CSRF/state protection aktif untuk OAuth redirect.
- Nonce validation aktif untuk OIDC.
- Email verified wajib untuk auto-link/register.
- Domain whitelist diterapkan sebelum create/link user.
- Callback error tidak menampilkan token atau secret.
- Audit log tidak menyimpan token mentah.
- User pending/inactive logout otomatis jika mencoba masuk panel.

### 8. Test Plan

- Login password existing berhasil.
- Google login user existing aktif berhasil.
- Google register user baru menjadi pending.
- Google email unverified ditolak.
- Google domain tidak diizinkan ditolak.
- SSO login user existing aktif berhasil.
- SSO register user baru menjadi pending.
- SSO callback invalid ditolak.
- Provider disabled tidak tampil dan route ditolak.
- User pending tidak bisa melihat menu admin.
- Admin approve user, lalu user bisa login dan melihat menu sesuai role.

## Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Salah link akun karena email sama | Wajib email verified, domain whitelist, dan unique provider subject |
| User baru langsung masuk admin | Default pending/inactive, role kosong sampai approve |
| Secret bocor di log | Sanitasi exception dan audit log |
| SSO provider berbeda spesifikasi | Finalisasi provider sebelum coding SSO |
| Konflik menu/permission | Sinkronkan seeder dan helper navigasi |

## Definition of Done

- OpenSpec change disetujui.
- Google login dan register pending berjalan.
- SSO login dan register pending berjalan sesuai provider final.
- User pending tidak bisa akses `/admin`.
- Admin dapat approve user baru dan assign role.
- Menu tampil sesuai permission setelah login.
- Test plan utama lulus.
- Dokumentasi `.env`, redirect URL, dan approval user tersedia.

