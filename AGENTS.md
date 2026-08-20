# AGENTS.md - VPoint Care / WACS

## Scope

Instruksi ini berlaku untuk seluruh repository.

`AGENTS.md` yang lebih dalam boleh menambahkan atau memperketat aturan untuk subtree-nya. Aturan yang lebih spesifik pada direktori target mengalahkan aturan umum bila tidak bertentangan dengan safety, data protection, dan instruksi eksplisit pengguna.

Tujuan utama dokumen ini adalah menyeimbangkan:

- kecepatan implementasi,
- penggunaan token/context,
- kualitas engineering,
- keselamatan data,
- konsistensi OpenSpec,
- dan kepatuhan terhadap arsitektur WACS.

Gunakan workflow paling ringan yang tetap aman dan cukup untuk menyelesaikan permintaan.

---

## Bahasa Kerja

- Gunakan Bahasa Indonesia untuk komunikasi, proposal, task, dan dokumentasi internal, kecuali pengguna meminta bahasa lain.
- Pertahankan nama class, method, tabel, kolom, route, command, konfigurasi, dan istilah teknis sesuai source code.

---

# 1. Klasifikasi Workflow

Sebelum melakukan pekerjaan, klasifikasikan task secara internal menjadi:

- `LEVEL 1 - FAST`
- `LEVEL 2 - BALANCED`
- `LEVEL 3 - MAX QUALITY`

Jangan meminta pengguna memilih level kecuali benar-benar diperlukan.

Gunakan level TERENDAH yang aman dan cukup.

Instruksi eksplisit pengguna selalu lebih tinggi prioritasnya.

Contoh:

- pengguna meminta `subagent-driven-development` -> gunakan LEVEL 3;
- pengguna meminta jangan pakai subagent -> jangan gunakan subagent;
- pengguna meminta langsung implementasi -> jangan berhenti hanya untuk meminta approval workflow;
- pengguna meminta plan/proposal saja -> jangan implementasi.

---

## LEVEL 1 - FAST

Gunakan untuk perubahan kecil, lokal, mekanis, dan berisiko rendah.

Contoh:

- typo;
- formatting;
- perubahan CSS kecil;
- perubahan label/text/localization sederhana;
- rename lokal;
- penyesuaian validation sederhana;
- perubahan satu file yang mekanis;
- bug kecil dengan root cause yang sudah jelas;
- perubahan sekitar 1-3 file tanpa perubahan kontrak, schema, security, atau business rule.

Workflow:

1. Periksa `git status`.
2. Baca file target dan dependency/caller yang benar-benar diperlukan.
3. Implementasikan langsung.
4. Jalankan validasi paling kecil yang relevan.
5. Periksa diff.
6. Selesai.

Aturan LEVEL 1:

- Jangan membuat OpenSpec baru.
- Jangan membuat plan document.
- Jangan menjalankan `writing-plans`.
- Jangan menjalankan `subagent-driven-development`.
- Jangan spawn reviewer/subagent.
- Jangan melakukan brainstorming jika requirement sudah jelas.
- Jangan membaca seluruh repository.
- Jangan menjalankan seluruh test suite jika targeted test sudah cukup.
- Jangan menjalankan Graphify bila lokasi perubahan sudah jelas.

---

## LEVEL 2 - BALANCED

Ini adalah DEFAULT untuk feature dan bug fix normal.

Contoh:

- perubahan sekitar 4-10 file;
- feature sedang;
- backend + frontend;
- repository/service/controller;
- bug lintas beberapa component;
- refactor terarah;
- perubahan flow yang tidak bersifat high-risk;
- implementasi dari OpenSpec atau plan yang sudah ada.

Workflow:

1. Periksa `git status`.
2. Temukan area source yang relevan.
3. Periksa OpenSpec aktif hanya jika kemungkinan beririsan.
4. Buat rencana singkat 3-7 langkah di context agent ATAU gunakan OpenSpec yang sudah ada.
5. Implementasikan secara berurutan di MAIN AGENT.
6. Jalankan targeted test/validation.
7. Lakukan satu final self-review.
8. Perbaiki issue material bila ditemukan.
9. Jalankan ulang validasi yang terpengaruh.
10. Periksa final diff dan selesai.

Aturan LEVEL 2:

- Prefer single-agent.
- Jangan menggunakan `subagent-driven-development` secara default.
- Jangan membuat reviewer terpisah untuk setiap task.
- Jika plan/OpenSpec valid sudah ada, gunakan itu; jangan membuat plan kedua.
- Jangan mengulang spec review setelah setiap micro-task.
- Kelompokkan pekerjaan berdasarkan deliverable, bukan per file.
- Gunakan OpenSpec hanya jika memenuhi aturan OpenSpec di bawah.

---

## LEVEL 3 - MAX QUALITY

Gunakan hanya bila biaya tambahan memang sepadan.

Trigger umum:

- modul baru besar;
- perubahan arsitektur;
- perubahan lintas domain;
- perubahan lebih dari sekitar 10-15 file bermakna;
- migration/schema/data transformation berisiko;
- authentication/authorization/security;
- financial/accounting;
- payroll;
- inventory costing;
- concurrency;
- distributed process;
- queue/idempotency kompleks;
- destructive operation;
- public API contract;
- perubahan besar pada WAHA/AI decision flow;
- pengguna meminta review maksimal;
- pengguna meminta SDD/subagent/multiple agents.

Workflow LEVEL 3 dapat memakai:

- OpenSpec;
- brainstorming;
- writing-plans;
- executing-plans;
- subagent-driven-development;
- TDD;
- reviewer terpisah;
- verification-before-completion.

Tetapi jangan menjalankan semua skill hanya karena tersedia.

Pilih hanya skill yang memberi manfaat nyata.

---

# 2. Kebijakan Superpowers

Superpowers adalah toolbox, bukan pipeline wajib.

## `subagent-driven-development`

JANGAN dipanggil otomatis.

Gunakan hanya jika:

1. task termasuk LEVEL 3; atau
2. pengguna secara eksplisit meminta:
   - `subagent-driven-development`;
   - SDD;
   - subagent;
   - multiple agents;
   - parallel agents.

Untuk LEVEL 1 dan LEVEL 2, gunakan MAIN AGENT.

## `writing-plans`

Gunakan bila:

- belum ada plan/OpenSpec yang valid;
- pekerjaan cukup kompleks untuk membutuhkan decomposition;
- atau pengguna memang meminta plan.

Jangan membuat `docs/superpowers/plans/...` hanya untuk menduplikasi OpenSpec yang sudah lengkap.

Jika OpenSpec sudah memuat proposal, acceptance criteria, dan tasks yang cukup, gunakan OpenSpec sebagai implementation plan.

## `executing-plans`

Prefer dibanding `subagent-driven-development` bila:

- plan sudah ada;
- task dapat dijalankan berurutan;
- pekerjaan didominasi write/edit;
- parallel agent tidak memberi keuntungan besar.

## `brainstorming`

Gunakan hanya jika:

- requirement benar-benar ambigu;
- ada keputusan desain penting yang belum dipilih;
- atau terdapat beberapa alternatif dengan trade-off nyata.

Jangan brainstorming untuk perubahan yang implementasinya sudah jelas.

## `test-driven-development`

Prioritaskan untuk:

- business rule;
- financial/accounting calculation;
- payroll calculation;
- inventory costing;
- regression bug;
- complex validation;
- critical domain logic.

Tidak perlu memaksakan full TDD ceremony untuk:

- CSS;
- text;
- localization sederhana;
- config mekanis;
- perubahan UI kosmetik.

## `verification-before-completion`

Selalu verifikasi perubahan bermakna.

Namun:

- gunakan validasi terkecil yang cukup;
- jangan menjalankan test suite mahal berulang tanpa alasan;
- ulangi test hanya jika perubahan setelah test dapat memengaruhi hasil.

---

# 3. Kebijakan Subagent

Subagent mahal dalam token dan context.

Setiap subagent harus mempunyai manfaat yang jelas.

Penggunaan yang baik:

- eksplorasi area repository yang independen;
- investigasi beberapa failure yang tidak saling bergantung;
- security review untuk perubahan berisiko;
- test/verification independen pada LEVEL 3;
- analisis arsitektur besar.

Penggunaan yang harus dihindari:

- satu agent per file;
- satu implementer per micro-task;
- satu reviewer per perubahan kecil;
- CRUD sederhana;
- CSS/UI kecil;
- sequential write yang dapat dilakukan main agent;
- spawn agent hanya karena capability tersedia.

Default:

`1 main agent + optional subagent hanya bila manfaatnya jelas`.

---

# 4. Context dan Token Efficiency

Jangan menghabiskan context tanpa kebutuhan.

Aturan:

- Search sebelum membaca banyak file.
- Baca file/range sekecil yang cukup.
- Jangan membuka ulang file yang belum berubah tanpa alasan.
- Jangan dump log besar bila potongan error sudah cukup.
- Jangan membaca seluruh directory secara rekursif untuk task lokal.
- Jangan mengeksplorasi module yang tidak terkait.
- Reuse pengetahuan repository yang baru diperoleh selama masih valid.
- Prefer targeted grep/search.
- Prefer targeted test.
- Jangan membuat dokumentasi tambahan kecuali diperlukan.
- Jangan mengulang analisis yang sudah dipastikan.
- Jangan membuat plan kedua jika plan pertama masih valid.
- Jangan melakukan review berulang setelah setiap micro-edit.

---

# 5. Konteks Repository

Jangan selalu membaca semua dokumen repository sebelum setiap perubahan.

Gunakan kebutuhan berdasarkan level.

## LEVEL 1

Minimal:

1. `AGENTS.md` yang berlaku.
2. File target.
3. Caller/dependency yang diperlukan.
4. `git status`.

Tidak wajib membaca seluruh:

- `README.md`;
- `openspec/project.md`;
- base spec;
- seluruh `openspec/changes/`.

## LEVEL 2

Baca sesuai relevansi:

1. `AGENTS.md`.
2. File target + caller/dependency.
3. `README.md` atau `openspec/project.md` hanya jika dibutuhkan untuk memahami architecture/convention.
4. OpenSpec aktif yang kemungkinan beririsan.
5. Base spec hanya pada capability yang sedang diubah.

## LEVEL 3

Sebelum planning/implementasi, baca yang relevan dari:

1. `README.md`;
2. `openspec/project.md`;
3. `openspec/specs/vpoint-care/spec.md`;
4. `AGENTS.md` yang berlaku;
5. source, migration, config, test, dan caller terkait;
6. change aktif di `openspec/changes/` yang beririsan.

Jangan membuat klaim tentang kondisi repository tanpa memeriksa source yang relevan.

---

# 6. OpenSpec

OpenSpec adalah source of truth untuk perubahan yang membutuhkan formal specification.

OpenSpec BUKAN kewajiban untuk semua edit source.

## OpenSpec wajib untuk

Gunakan atau perbarui OpenSpec untuk perubahan seperti:

- feature baru yang bermakna;
- business rule;
- perubahan data contract;
- database/schema/migration/index/relationship;
- authentication/authorization/role/permission;
- public/internal API contract yang dipakai lintas component;
- webhook payload/behavior penting;
- queue/event/scheduler dengan behavior baru;
- AI decision flow atau WAHA delivery behavior yang bermakna;
- perubahan lintas domain;
- perubahan arsitektur/service boundary;
- perubahan berisiko tinggi;
- pekerjaan dengan acceptance criteria yang perlu disepakati;
- LEVEL 3.

## OpenSpec biasanya tidak diperlukan untuk

- investigasi tanpa perubahan;
- penjelasan;
- typo;
- formatting;
- komentar;
- dokumentasi kecil;
- CSS kosmetik;
- localization sederhana;
- bug kecil tanpa perubahan business rule/contract/data;
- refactor lokal tanpa perubahan behavior;
- perubahan mekanis satu file;
- test/lint/build/diagnostic command;
- LEVEL 1.

## LEVEL 2 dan OpenSpec

LEVEL 2 tidak otomatis membutuhkan OpenSpec.

Gunakan OpenSpec jika perubahan menyentuh:

- business behavior;
- contract;
- data;
- permission/security;
- significant UX flow;
- atau acceptance criteria yang perlu dipertahankan.

Jika change relevan sudah ada:

- perbarui change tersebut;
- jangan membuat duplikat;
- jangan membuat plan lain hanya untuk mengulang informasi yang sama.

---

## Struktur OpenSpec

Gunakan:

```text
openspec/changes/<change-slug>/
|-- proposal.md
|-- tasks.md
`-- specs/
    `-- vpoint-care/
        `-- spec.md
```

Gunakan `<change-slug>` dalam `kebab-case`, singkat, spesifik, dan berorientasi tindakan.

Contoh:

- `fix-waha-message-deduplication`
- `add-ticket-assignment-history`

OpenSpec adalah sumber kebenaran perubahan formal.

Folder `docs/` boleh dipakai untuk:

- audit;
- diagram;
- deployment guide;
- dokumentasi pendukung.

Jangan membuat duplicate implementation plan di `docs/` jika OpenSpec sudah cukup.

---

# 7. Urutan Kerja OpenSpec

Jika task memang membutuhkan OpenSpec:

1. Pelajari flow aktual yang relevan.
2. Periksa `git status`.
3. Cari change aktif yang benar-benar beririsan.
4. Buat/perbarui `proposal.md`.
5. Buat/perbarui delta spec.
6. Buat/perbarui `tasks.md`.
7. Implementasikan bila pengguna sudah meminta implementasi.
8. Tandai task selesai hanya setelah diverifikasi.
9. Jalankan validasi.
10. Sinkronkan OpenSpec dengan implementasi final.

Jangan membuat OpenSpec sebagai formalitas setelah kode selesai.

## Approval policy

Jangan meminta approval berulang jika intent pengguna sudah jelas.

Kalimat seperti:

- "implementasikan";
- "kerjakan";
- "perbaiki";
- "tambahkan fitur ini";
- "execute plan ini";

dianggap sebagai izin implementasi dalam scope yang diminta.

Minta approval ulang hanya jika:

- scope harus diperluas secara material;
- diperlukan destructive action;
- ada perubahan kontrak/behavior penting yang tidak terdapat pada requirement awal;
- terdapat pilihan desain besar yang tidak dapat diputuskan aman dari context.

Jika pengguna hanya meminta proposal/plan, jangan implementasi.

---

# 8. Isi `proposal.md`

Untuk change yang membutuhkan proposal formal, minimal berisi:

- `# Change: <judul>`
- `## Summary`
- `## Problem Statement`
- `## Current State`
- `## Goals`
- `## Non-Goals`
- `## Proposed Changes`
- `## Impacted Areas`
- `## Risks and Mitigations`
- `## Validation`

Tambahkan `## Rollback` jika ada:

- migration;
- transformasi data;
- deployment berisiko;
- destructive/irreversible behavior.

Jangan menulis klaim current state tanpa memeriksa source.

---

# 9. Isi Delta Spec

Gunakan requirement dan scenario yang dapat diuji:

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

Ketentuan:

- Jelaskan behavior, bukan detail implementasi semata.
- Setiap requirement minimal mempunyai satu scenario.
- Sertakan success path dan failure/validation path bila relevan.
- Sertakan permission/security bila tersentuh.
- Sertakan SQL Server compatibility, retry/idempotency, localization, atau observability hanya bila memang relevan.
- Hindari `TBD`, `TODO`, `sesuai kebutuhan`, atau istilah ambigu.

Jangan menambahkan scenario spekulatif yang tidak berkaitan dengan scope.

---

# 10. Isi `tasks.md`

Task harus berupa deliverable bermakna.

Target granularitas:

- small change: tidak perlu formal tasks;
- medium change: sekitar 3-7 task;
- large change: sekitar 5-10 task utama.

Hindari 20-50 micro-task kecuali benar-benar diperlukan.

Gunakan:

- checkbox `- [ ]`;
- grouping bernomor jika membantu;
- dependency order;
- file/path bila berguna;
- output yang dapat diverifikasi.

Contoh grouping yang baik:

1. Database/domain
2. Backend/business logic
3. API/integration
4. UI/permission/localization
5. Test/verification/deployment

Hindari pola:

1. create file A
2. add method A
3. add import A
4. add property A
5. review A
6. repeat untuk setiap file

Tambahkan:

- backup/rollback bila migration/data change;
- `npm run build` bila frontend asset berubah;
- queue/Reverb/scheduler restart hanya bila runtime terkait berubah.

Jangan memasukkan refactor spekulatif atau pekerjaan "untuk nanti".

---

# 11. Aturan Teknis WACS / CareDesk

- Pertahankan PHP 8.3+, Laravel 13, Filament 5, dan kompatibilitas PostgreSQL 16+ (default SaaS).
- Pertahankan dukungan migrasi universal (PostgreSQL, SQL Server, MySQL/MariaDB).
- Pertahankan `MPengguna` sebagai sumber autentikasi; jangan kembali ke tabel `users` default.
- Pertahankan kontrak route penting: `/admin`, `/webhooks/waha/{token?}`, `/admin/waha-media/{message}`, dan `/profile-storage/{path}`.
- Pertahankan normalisasi WAHA untuk `@c.us`, `@s.whatsapp.net`, `@g.us`, dan `@lid`.
- Webhook harus cepat, tervalidasi, idempotent, dan memindahkan pekerjaan berat ke queue.
- Perubahan queue harus menentukan queue name, timeout, retry, deduplication, dan failure behavior bila aspek tersebut memang berubah.
- Perubahan AI harus mempertimbangkan provider, model, API key, jam kerja, hari libur, nomor pengecualian, knowledge, session, dan `KirimKeWaha` sesuai area yang tersentuh.
- Jangan menampilkan atau mencatat API key, webhook token, password, access token, atau secret lain.
- Perubahan menu/akses harus menyelaraskan `AccessPermissions`, `FilamentAccess`, `NavigationHelper`, resource/page visibility, seeder, dan label `id`/`en` bila relevan.
- Perubahan UI user-facing wajib mendukung Bahasa Indonesia dan Inggris; jangan hardcode string bila localization key sesuai.
- Jangan mengubah file di `src/vendor/`, generated asset, lock file, atau dependency tanpa alasan yang jelas.

---

# 12. Database dan Data Safety

- Periksa migration, `src/script/DATABASE_SCHEMA_POSTGRESQL.sql`, dan `src/script/DATABASE_SCHEMA_WACS.sql` sebelum mengubah schema.
- Migration harus aman untuk fresh install dan database existing sejauh scope memerlukannya.
- Gunakan transaksi untuk rangkaian write yang harus atomik.
- Pertahankan UUID dan konvensi nama tabel/kolom master (M) dan transaksi (T).
- Jangan menghapus atau mengubah data produksi secara destruktif tanpa backup, rollback, dan persetujuan eksplisit.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, reset database, atau command destruktif lainnya tanpa permintaan eksplisit pengguna.

Safety rules ini berlaku pada semua workflow level.

---

# 13. Minimalisme Implementasi

- Cari helper, service, model, dan pola yang sudah ada sebelum membuat abstraksi baru.
- Perbaiki root cause pada shared path jika memang merupakan sumber masalah.
- Hindari dependency baru jika framework, standard library, atau dependency terpasang sudah cukup.
- Hindari interface satu implementasi, factory satu produk, config spekulatif, dan scaffolding "untuk nanti".
- Jaga diff sekecil mungkin tanpa mengorbankan validation, security, data safety, accessibility, atau kebutuhan eksplisit.
- Jangan melakukan refactor tidak terkait hanya karena ditemukan saat membaca source.
- Jangan memperluas scope tanpa alasan yang diperlukan.

---

# 14. Validasi

Mulai dari command yang paling spesifik.

Contoh:

```powershell
cd src
php -l path/to/changed-file.php
php artisan test --filter=RelevantTest
php artisan test
vendor/bin/pint --test
npm run build
```

Pemilihan berdasarkan scope:

## LEVEL 1

Prefer:

- syntax check;
- targeted test;
- targeted lint;
- build hanya jika diperlukan.

## LEVEL 2

Prefer:

1. targeted test;
2. relevant module test;
3. broader test jika perubahan berpotensi menyebar.

## LEVEL 3

Gunakan validation matrix yang sesuai risiko, termasuk broader test/build bila relevan.

Jangan menjalankan command yang sama berkali-kali tanpa perubahan yang dapat memengaruhi hasil.

Jika integrasi/database tidak dapat diuji penuh:

- dokumentasikan apa yang sudah diuji;
- apa yang belum dapat diuji;
- langkah verifikasi manual yang tepat.

Jangan menyatakan test lulus jika command tidak dijalankan atau gagal karena environment.

---

# 15. Definition of Done

Perubahan selesai jika sesuai dengan level dan scope-nya.

Minimal:

- behavior yang diminta telah diimplementasikan;
- perubahan pengguna yang sudah ada tidak tertimpa;
- validasi relevan sudah dijalankan;
- final diff telah diperiksa;
- tidak ada secret/debug code/perubahan tidak terkait.

Untuk perubahan dengan OpenSpec:

- implementasi sesuai proposal;
- scenario yang disetujui terpenuhi;
- task selesai dicentang;
- OpenSpec sinkron dengan hasil final;
- deployment/migration action dijelaskan.

Security, data safety, permission, localization, dan deployment impact diperiksa sesuai area yang benar-benar tersentuh.

---

# 16. Pelaporan Akhir

Laporan akhir harus ringkas.

Sebutkan:

- apa yang berubah;
- file/area utama;
- validation/test yang dijalankan dan hasilnya;
- migration/deployment action bila ada;
- risiko atau task tersisa bila ada.

Jika menggunakan OpenSpec, sebutkan change yang digunakan.

Jangan mengulang seluruh plan atau seluruh diff dalam laporan akhir.

---

# 17. Graphify

Project mempunyai knowledge graph di `graphify-out/`.

Gunakan Graphify sebagai accelerator, bukan langkah wajib untuk semua task.

## Gunakan Graphify bila

- pertanyaan arsitektur;
- mencari hubungan antar-module;
- belum tahu lokasi implementasi;
- perubahan lintas beberapa component;
- LEVEL 2/3 yang membutuhkan eksplorasi;
- `graphify query` dapat menghindari membaca banyak file.

Prefer:

```text
graphify query "<question>"
graphify path "<A>" "<B>"
graphify explain "<concept>"
```

Jika `graphify-out/wiki/index.md` ada, gunakan untuk broad navigation.

Baca `graphify-out/GRAPH_REPORT.md` hanya untuk:

- broad architecture review;
- atau jika query/path/explain belum cukup.

## Tidak perlu Graphify bila

- lokasi file sudah diketahui;
- perubahan LEVEL 1;
- task hanya typo/CSS/local edit;
- pengguna memberi file/path target yang jelas.

Dirty `graphify-out/` bukan alasan untuk melewatkan Graphify bila memang dibutuhkan.

Setelah perubahan:

- jalankan `graphify update .` untuk perubahan structural atau relationship code yang bermakna;
- tidak wajib untuk typo, docs-only, CSS kosmetik, atau perubahan lokal yang tidak memengaruhi graph secara berarti.

---

# 18. Ringkasan Keputusan Workflow

Gunakan pola berikut:

```text
Apakah task kecil, lokal, low-risk?
|
+-- YA -> LEVEL 1
|         direct edit
|         targeted validation
|         no OpenSpec baru
|         no subagent
|
+-- TIDAK
    |
    Apakah task high-risk / architectural / large / user meminta SDD?
    |
    +-- YA -> LEVEL 3
    |         OpenSpec
    |         optional TDD
    |         optional subagent/reviewer
    |         broader verification
    |
    +-- TIDAK -> LEVEL 2
              concise plan / existing OpenSpec
              MAIN AGENT
              targeted implementation
              one review
              verification
```

Prinsip terakhir:

> Jangan menggunakan workflow yang lebih mahal hanya karena tersedia. Gunakan workflow paling ringan yang tetap menghasilkan perubahan yang benar, aman, dapat diverifikasi, dan sesuai scope pengguna.
