# AGENTS.md - VPoint Care / WACS

## Scope

Instruksi ini berlaku untuk seluruh repository. `AGENTS.md` yang lebih dalam dapat menambahkan atau memperketat aturan untuk subtree-nya, tetapi tidak boleh menghapus kewajiban OpenSpec di dokumen ini.

## Bahasa Kerja

- Gunakan Bahasa Indonesia untuk komunikasi, proposal, task, dan dokumentasi internal, kecuali pengguna meminta bahasa lain.
- Pertahankan nama class, method, tabel, kolom, route, command, konfigurasi, dan istilah teknis sesuai source code.

## Konteks Wajib

Sebelum membuat rencana atau mengubah source code, baca minimal:

1. `README.md`.
2. `openspec/project.md`.
3. `openspec/specs/vpoint-care/spec.md`.
4. `AGENTS.md` yang berlaku pada file target.
5. Source code, migration, konfigurasi, test, dan caller yang terkait langsung.
6. Change aktif di `openspec/changes/` yang mungkin beririsan.

Jangan membuat rencana hanya dari permintaan pengguna. Verifikasi kondisi aktual repository terlebih dahulu.

## OpenSpec Sebagai Standar Perencanaan

Setiap perubahan non-trivial wajib direncanakan di:

```text
openspec/changes/<change-slug>/
|-- proposal.md
|-- tasks.md
`-- specs/
    `-- vpoint-care/
        `-- spec.md
```

Gunakan `<change-slug>` berformat `kebab-case`, singkat, spesifik, dan berorientasi tindakan, misalnya `fix-waha-message-deduplication` atau `add-ticket-assignment-history`.

Jangan menyimpan plan baru hanya di percakapan, komentar source code, atau folder `docs/`. Folder `docs/` boleh berisi dokumentasi pendukung, audit, diagram, atau deployment guide, tetapi sumber kebenaran rencana perubahan tetap `openspec/changes/<change-slug>/`.

## Perubahan yang Wajib Memakai OpenSpec

OpenSpec wajib dibuat atau diperbarui untuk:

- Fitur baru atau perubahan perilaku pengguna.
- Bug fix yang mengubah business rule, data, kontrak, atau alur proses.
- Refactor lintas file/modul atau perubahan boundary service.
- Perubahan database, migration, index, seed, tabel, atau relasi.
- Perubahan route, webhook, API, payload, queue, event, scheduler, atau command.
- Perubahan autentikasi, otorisasi, role, permission, session, atau secret handling.
- Perubahan AI provider, prompt, knowledge retrieval, auto-reply, atau keputusan pengiriman WAHA.
- Perubahan UI flow, navigasi, form, aksi, validasi, atau localization.
- Perubahan deployment, container, runtime process, dependency, atau environment variable.
- Pekerjaan yang menyentuh lebih dari satu domain utama.
- Pekerjaan yang membutuhkan keputusan desain atau acceptance criteria.

## Pengecualian OpenSpec

OpenSpec baru tidak diperlukan untuk:

- Investigasi atau penjelasan tanpa perubahan file.
- Koreksi typo dan formatting yang tidak mengubah perilaku.
- Perubahan komentar atau dokumentasi kecil yang hanya menyelaraskan fakta yang sudah ada.
- Perubahan sangat kecil dan mekanis pada satu file tanpa dampak kontrak, data, security, atau UX.
- Menjalankan test, formatter, lint, build, atau command diagnostik tanpa perubahan source.

Jika ragu apakah perubahan trivial, anggap non-trivial dan gunakan OpenSpec. Jika change yang relevan sudah ada, perbarui change tersebut; jangan membuat duplikat.

## Urutan Kerja Wajib

1. Pelajari repository dan telusuri alur nyata end-to-end.
2. Periksa `git status` dan jangan menimpa perubahan pengguna.
3. Cari change OpenSpec aktif yang beririsan.
4. Buat atau perbarui `proposal.md`.
5. Buat atau perbarui delta spec di `specs/vpoint-care/spec.md`.
6. Buat atau perbarui `tasks.md` dengan checkbox dan urutan implementasi.
7. Tampilkan ringkasan plan dan minta persetujuan pengguna sebelum implementasi, kecuali pengguna secara eksplisit meminta eksekusi langsung atas plan yang sudah disetujui.
8. Implementasikan task secara berurutan dan minimal.
9. Tandai checkbox yang benar-benar selesai; jangan menandai task yang belum diverifikasi.
10. Jalankan validasi paling spesifik, kemudian validasi yang lebih luas bila relevan.
11. Sinkronkan proposal, spec, tasks, dokumentasi, dan hasil implementasi sebelum menyatakan selesai.

Agent tidak boleh menggunakan plan sebagai formalitas setelah kode selesai. OpenSpec harus dibuat sebelum implementasi untuk perubahan non-trivial.

## Isi `proposal.md`

`proposal.md` harus cukup jelas untuk reviewer dan minimal berisi:

- `# Change: <judul>`.
- `## Summary`.
- `## Problem Statement` berdasarkan kondisi source code aktual.
- `## Current State` dengan file, class, tabel, atau flow terkait.
- `## Goals` dan `## Non-Goals`.
- `## Proposed Changes`.
- `## Impacted Areas` berisi file/modul, database, API, permission, localization, queue, dan deployment yang relevan.
- `## Risks and Mitigations`.
- `## Validation`.
- `## Rollback` untuk migration, data, deployment, atau perubahan berisiko.

Jangan menulis klaim kondisi saat ini tanpa memeriksa source code.

## Isi Delta Spec

Gunakan format requirement dan scenario yang dapat diuji:

```markdown
## Requirements

### Requirement: Nama Kemampuan

Sistem SHALL ...

#### Scenario: Nama skenario

- **GIVEN** kondisi awal
- **WHEN** aksi atau event terjadi
- **THEN** hasil yang dapat diverifikasi
- **AND** constraint tambahan
```

Ketentuan delta spec:

- Jelaskan perilaku, bukan detail implementasi semata.
- Setiap requirement harus mempunyai minimal satu scenario.
- Sertakan success path, permission/security, validation, dan failure path bila relevan.
- Sertakan kompatibilitas SQL Server, queue retry/idempotency, localization, dan observability bila tersentuh.
- Gunakan nilai dan kontrak konkret; hindari `TBD`, `TODO`, `sesuai kebutuhan`, atau kata ambigu.

## Isi `tasks.md`

- Gunakan checkbox `- [ ]` dan kelompok bernomor.
- Susun berdasarkan dependency: schema, model/service, UI/API, permission/localization, test, lalu deployment.
- Sebutkan path file yang akan dibuat atau diubah.
- Setiap task harus menghasilkan output yang dapat diverifikasi.
- Sertakan task test dan command validasi dengan hasil yang diharapkan.
- Sertakan database backup dan rollback bila ada migration/data change.
- Sertakan `npm run build` bila frontend asset berubah.
- Sertakan restart queue/Reverb/scheduler bila runtime terkait berubah.
- Jangan memasukkan refactor spekulatif atau pekerjaan `untuk nanti`.

## Persetujuan dan Perubahan Scope

- Jangan mengimplementasikan perubahan non-trivial sebelum plan disetujui pengguna.
- Persetujuan terhadap proposal berarti scope, bukan izin untuk menambah fitur terkait secara otomatis.
- Jika implementasi menemukan kebutuhan baru yang mengubah scope, berhenti pada batas aman, perbarui OpenSpec, dan minta persetujuan ulang.
- Perbaikan kecil yang diperlukan agar build/test tetap berjalan boleh ditambahkan ke plan tanpa memperluas perilaku produk.

## Aturan Teknis WACS

- Pertahankan PHP 8.3+, Laravel 13, Filament 5, dan kompatibilitas Microsoft SQL Server.
- Jangan mengasumsikan sintaks atau perilaku MySQL/PostgreSQL.
- Pertahankan `MPengguna` sebagai sumber autentikasi; jangan kembali ke tabel `users` default.
- Pertahankan kontrak route penting: `/admin`, `/webhooks/waha/{token?}`, `/admin/waha-media/{message}`, dan `/profile-storage/{path}`.
- Pertahankan normalisasi WAHA untuk `@c.us`, `@s.whatsapp.net`, `@g.us`, dan `@lid`.
- Webhook harus cepat, tervalidasi, idempotent, dan memindahkan pekerjaan berat ke queue.
- Perubahan queue harus menentukan queue name, timeout, retry, deduplication, dan failure behavior.
- Perubahan AI harus mempertimbangkan provider, model, API key, jam kerja, hari libur, nomor pengecualian, knowledge, session, dan `KirimKeWaha`.
- Jangan menampilkan atau mencatat API key, webhook token, password, access token, atau secret lain.
- Perubahan menu/akses harus menyelaraskan `AccessPermissions`, `FilamentAccess`, `NavigationHelper`, resource/page visibility, seeder, dan label `id`/`en`.
- Perubahan UI user-facing wajib mendukung Bahasa Indonesia dan Inggris; jangan hardcode string bila localization key sesuai.
- Jangan mengubah file di `src/vendor/`, generated asset, lock file, atau dependency tanpa alasan yang tercatat di proposal.

## Database dan Data Safety

- Periksa migration dan `src/script/DATABASE_SCHEMA_WACS.sql` sebelum mengubah schema.
- Migration harus aman untuk fresh install dan database existing sejauh scope memerlukannya.
- Gunakan transaksi untuk rangkaian write yang harus atomik.
- Pertahankan UUID dan konvensi nama tabel/kolom legacy.
- Jangan menghapus atau mengubah data produksi secara destruktif tanpa backup, rollback, dan persetujuan eksplisit.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, reset database, atau command destruktif lainnya tanpa permintaan eksplisit pengguna.

## Minimalisme Implementasi

- Cari helper, service, model, dan pola yang sudah ada sebelum membuat abstraksi baru.
- Perbaiki root cause pada shared path, bukan menambah patch di setiap caller.
- Hindari dependency baru jika framework, standard library, atau dependency terpasang sudah cukup.
- Hindari interface satu implementasi, factory satu produk, config spekulatif, dan scaffolding `untuk nanti`.
- Jaga diff sekecil mungkin tanpa mengorbankan validation, security, data safety, accessibility, atau kebutuhan eksplisit.
- Jangan melakukan refactor tidak terkait hanya karena ditemukan saat membaca source.

## Validasi

Pilih command sesuai perubahan dan mulai dari yang paling spesifik:

```powershell
cd src
php -l path/to/changed-file.php
php artisan test --filter=RelevantTest
php artisan test
vendor/bin/pint --test
npm run build
```

Untuk perubahan database atau integrasi yang tidak dapat diuji penuh di environment lokal, dokumentasikan validasi yang sudah dilakukan, yang belum dapat dilakukan, dan langkah verifikasi manual yang tepat. Jangan menyatakan test lulus jika command tidak dijalankan atau gagal karena environment.

## Definition of Done

Perubahan dinyatakan selesai hanya jika:

- Implementasi sesuai proposal dan seluruh scenario yang disetujui.
- Task yang selesai telah dicentang dan task tersisa dijelaskan.
- Test/validation relevan telah dijalankan dengan hasil dicatat.
- Security, data safety, permission, localization, dan deployment impact telah diperiksa.
- OpenSpec sesuai dengan implementasi final.
- Tidak ada secret, debug code, placeholder, atau perubahan tidak terkait.
- Perubahan pengguna yang sudah ada tidak tertimpa.

## Pelaporan Akhir

Laporan akhir agent harus menyebutkan secara ringkas:

- Change OpenSpec yang digunakan.
- File utama yang berubah.
- Perilaku yang selesai dibuat atau diperbaiki.
- Command validasi dan hasilnya.
- Migration/deployment action yang masih harus dilakukan.
- Risiko, batasan, atau task yang belum selesai.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
