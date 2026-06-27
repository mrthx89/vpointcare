# Tasks: Login dan Register User via Google dan SSO

## 1. Discovery dan Keputusan Konfigurasi

- [ ] Gunakan OIDC/OAuth2 sebagai default SSO; aktifkan SAML hanya jika IdP perusahaan mewajibkan.
- [ ] Aktifkan registrasi eksternal dengan status pending approval, bukan akses langsung.
- [ ] Wajibkan whitelist domain email untuk Google dan SSO.
- [ ] Pakai status default user baru `pending` atau status internal ekuivalen.
- [ ] Biarkan user baru tanpa role sampai admin approve dan assign role.

## 2. Data Model dan Migration

- [ ] Review struktur `MPengguna`, `MPeran`, `MHakAkses`, dan field status aktif existing.
- [ ] Tambahkan tabel/field link identitas eksternal bila belum tersedia.
- [ ] Simpan `provider`, `provider_user_id`, `email`, `name`, `avatar_url`, `linked_at`, dan metadata aman seperlunya.
- [ ] Pastikan unique constraint mencegah satu identitas provider tertaut ke lebih dari satu user.
- [ ] Pastikan migration kompatibel dengan SQL Server.

## 3. Konfigurasi Provider

- [ ] Tambahkan config Google OAuth client id, client secret, redirect URL, allowed domains, dan enabled flag.
- [ ] Tambahkan config SSO client/entity setting sesuai tipe provider final.
- [ ] Pastikan secret hanya dibaca dari `.env`, bukan hardcoded.
- [ ] Tambahkan contoh variabel `.env.example` atau dokumentasi deployment bila file contoh tidak dipakai.

## 4. Route dan Service Auth Eksternal

- [ ] Buat route redirect Google dan callback Google.
- [ ] Buat route redirect SSO dan callback SSO.
- [ ] Buat service untuk normalisasi profil eksternal menjadi format internal.
- [ ] Implementasikan pencarian user berdasarkan provider link dan email terverifikasi.
- [ ] Implementasikan pembuatan user pending untuk registrasi baru bila diizinkan.
- [ ] Implementasikan validasi domain, status user, role, dan permission sebelum login sukses.

## 5. UI Login dan Register

- [ ] Tambahkan tombol **Masuk dengan Google** dengan ikon Google, border halus, hover state, dan loading state.
- [ ] Tambahkan tombol **Masuk dengan SSO Perusahaan** dengan ikon shield/building, warna brand, hover state, dan loading state.
- [ ] Tambahkan halaman/message pending approval yang ramah, jelas, dan tidak membocorkan detail teknis.
- [ ] Buat layout login responsive dengan logo, subtitle keamanan, divider, error state aman, dan dark-mode support.
- [ ] Pastikan login password existing tetap tersedia.

## 6. Approval User Baru dan Menu Admin

- [ ] Cek apakah resource user existing sudah bisa melihat user pending.
- [ ] Jika belum, tambahkan filter/status pending pada resource pengguna.
- [ ] Tambahkan aksi approve/reject user pending sesuai pola resource existing.
- [ ] Sinkronkan menu/permission melalui `AccessPermissions`, `NavigationHelper`, dan seeder bila ada menu baru.
- [ ] Pastikan user pending tidak melihat menu admin sebelum disetujui.

## 7. Security dan Audit

- [ ] Validasi OAuth `state`/nonce dan redirect URL.
- [ ] Wajibkan email terverifikasi untuk auto-link atau registrasi.
- [ ] Jangan log client secret, token, atau claim sensitif.
- [ ] Catat audit event login sukses, login gagal, register pending, approval, reject, dan unlink.
- [ ] Tambahkan rate limit pada endpoint redirect/callback jika sesuai pola aplikasi.

## 8. Testing dan Validasi

- [ ] Test login password existing tetap berhasil.
- [ ] Test login Google untuk user existing aktif.
- [ ] Test register Google untuk user baru menjadi pending.
- [ ] Test login SSO untuk user existing aktif.
- [ ] Test register SSO untuk user baru menjadi pending.
- [ ] Test domain tidak diizinkan ditolak.
- [ ] Test user inactive/pending tidak bisa masuk panel.
- [ ] Test callback error tidak membuka akses dan menampilkan pesan aman.
- [ ] Test permission/menu setelah approval user.

## 9. Dokumentasi Deployment

- [ ] Dokumentasikan cara membuat Google OAuth Client.
- [ ] Dokumentasikan redirect URL untuk environment dev/staging/prod.
- [ ] Dokumentasikan konfigurasi SSO sesuai provider final.
- [ ] Dokumentasikan prosedur approve user baru.
- [ ] Dokumentasikan rollback config jika provider bermasalah.

