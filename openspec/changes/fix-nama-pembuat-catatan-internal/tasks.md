# Tasks: Perbaiki Nama Pembuat Catatan Internal Chat

## 1. Helper Bersama

- [ ] Buat `src/app/Filament/Concerns/ResolvesCatatanInternal.php` dengan trait `ResolvesCatatanInternal`.
- [ ] Implementasikan `protected function catatanInternalRows(string $chatId): array` yang mengambil `TChatDCatatanInternal` untuk `IdChat` tersebut, urut `TglBuat` menaik.
- [ ] Kumpulkan seluruh `DibuatOleh` yang tidak kosong menjadi daftar unik, lalu lakukan **satu** query `DB::table('MPengguna')->whereIn('Id', $ids)->pluck('NamaPengguna', 'Id')`.
- [ ] Jangan memfilter `NonAktif` pada query tersebut agar nama pengguna nonaktif tetap tampil pada catatan historis.
- [ ] Kembalikan setiap baris dengan kunci `Id`, `IsiCatatan`, `TglBuat`, `TglFormatted` (`LocaleFormatter::dateTime`), `DibuatOlehNama`, dan `NamaPembuat`; dua kunci nama terakhir berisi nilai yang sama agar blade existing tidak perlu diubah.
- [ ] Pakai `__('ui.common.system')` bila `DibuatOleh` kosong, tidak ditemukan pada hasil pluck, atau namanya string kosong.
- [ ] Verifikasi: `php -l app/Filament/Concerns/ResolvesCatatanInternal.php` lulus.

## 2. Inbox WhatsApp

- [ ] Pada `src/app/Filament/Pages/InboxWhatsapp.php`, tambahkan `use App\Filament\Concerns\ResolvesCatatanInternal;` dan pakai trait tersebut pada class.
- [ ] Ganti isi `loadInternalNotes()` agar memanggil `catatanInternalRows($this->selectedChatId)`, mempertahankan early-return `[]` saat `selectedChatId` kosong.
- [ ] Hapus blok `Schema::hasTable('Pengguna')` dan `DB::table('Pengguna')` pada baris 324-325.
- [ ] Pastikan `saveInternalNote()` tidak diubah; pengisian `DibuatOleh` sudah benar.
- [ ] Verifikasi: `php -l app/Filament/Pages/InboxWhatsapp.php` lulus.

## 3. Detail Sesi Chat

- [ ] Pada `src/app/Filament/Pages/ViewChatSession.php`, tambahkan dan pakai trait yang sama.
- [ ] Ganti blok pemuatan catatan internal di `loadSession()` (baris 152-171) agar memanggil `catatanInternalRows($this->sessionId)`.
- [ ] Hapus variabel `$hasPengguna` dan seluruh referensi `DB::table('Pengguna')`.
- [ ] Hapus `use Illuminate\Support\Facades\Schema;` bila setelah perubahan tidak ada pemakaian `Schema` lain pada file tersebut.
- [ ] Verifikasi: `php -l app/Filament/Pages/ViewChatSession.php` lulus.

## 4. Guard Anti-Regresi

- [ ] Jalankan pencarian `DB::table('Pengguna')` dan `Schema::hasTable('Pengguna')` pada `src/app`; hasil yang diharapkan: **0 kemunculan**.
- [ ] Pastikan blade `resources/views/filament/pages/inbox-whatsapp.blade.php` dan `view-chat-session.blade.php` tidak diubah dan tetap membaca kunci array yang sama seperti sebelumnya.
- [ ] Pastikan tidak ada key translation baru yang ditambahkan; `ui.common.system` sudah tersedia pada `resources/lang/id/ui.php` dan `resources/lang/en/ui.php`.

## 5. Test

- [ ] Buat `src/tests/Feature/CatatanInternalTest.php`.
- [ ] Test: catatan dengan `DibuatOleh` valid mengembalikan `NamaPengguna` pengguna tersebut.
- [ ] Test: catatan dengan `DibuatOleh` NULL mengembalikan `ui.common.system`.
- [ ] Test: catatan dengan `DibuatOleh` merujuk ID yang tidak ada mengembalikan `ui.common.system`.
- [ ] Test: catatan dari pengguna `NonAktif = 1` tetap mengembalikan `NamaPengguna`.
- [ ] Test: memuat 5 catatan dari 2 pengguna hanya menghasilkan satu query ke `MPengguna` (pakai `DB::listen` atau `assertQueryCount` sederhana).
- [ ] Verifikasi: `php artisan test --filter=CatatanInternal` lulus.

## 6. Validasi Menyeluruh

- [ ] Jalankan `php artisan test` dan catat hasilnya.
- [ ] Jalankan `vendor\bin\pint --test` dan pastikan tidak ada pelanggaran format pada file yang diubah.
- [ ] `npm run build` **tidak** diperlukan karena tidak ada perubahan asset frontend.

## 7. Verifikasi Manual

- [ ] Login, buka Inbox WhatsApp, pilih chat, tambahkan catatan internal → nama pengguna login tampil.
- [ ] Muat ulang halaman → nama tetap tampil.
- [ ] Buka Histori Chat → Detail Sesi Chat pada chat yang sama → nama pembuat identik dengan yang tampil di Inbox.
- [ ] Periksa satu catatan yang dibuat sebelum perbaikan → namanya kini tampil tanpa perbaikan data.

## 8. Deployment

- [ ] Deploy kode; tidak ada migration dan tidak ada perubahan data.
- [ ] Jalankan `php artisan optimize:clear` lalu `php artisan optimize`.
- [ ] Restart queue/scheduler/Reverb **tidak** diperlukan karena tidak ada perubahan job, event, atau command.
- [ ] Backup database **tidak** diperlukan karena perubahan bersifat read-only terhadap data existing.
