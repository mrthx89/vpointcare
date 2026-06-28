# Plan Implementasi: AI Learning dari Chat Customer

## Tujuan

Membuat AI Agent bertambah pintar dari percakapan customer, tetapi tetap aman. Sistem tidak melakukan fine-tuning otomatis. Sistem membuat **draft knowledge** dari chat customer, lalu manusia meninjau sebelum knowledge aktif dipakai AI Auto Reply.

## Keputusan Arsitektur

Pendekatan terbaik untuk source code ini adalah **Human-in-the-loop RAG**.

Alasannya:

- Source sudah punya tabel dan UI `MPengetahuan` untuk Knowledge Base AI.
- Auto-reply sudah membaca `MPengetahuan` ke prompt.
- Fine-tuning otomatis dari chat customer berisiko memasukkan data salah dan PII.
- Review admin menjaga kualitas jawaban AI.
- Implementasi bertahap lebih aman untuk aplikasi production WhatsApp customer service.

## Flow Utama

1. Customer dan CS melakukan chat seperti biasa.
2. User berizin membuka chat yang sudah selesai atau relevan.
3. User klik **Buat Draft Knowledge**.
4. Sistem mengambil riwayat chat, menyaring pesan, dan menyamarkan data sensitif.
5. AI membuat kandidat knowledge dalam format JSON.
6. Sistem menyimpan kandidat ke `TAiDraftPengetahuan` dengan status `Draft`.
7. Admin/supervisor review draft.
8. Jika disetujui, draft masuk ke `MPengetahuan`.
9. Auto-reply berikutnya bisa memakai knowledge baru tersebut.

## Fase 1: Aman dan Manual

Fase pertama dibuat manual, bukan otomatis.

### Fitur

- Tabel draft knowledge.
- Service ekstraksi knowledge dari chat.
- Tombol manual dari halaman chat.
- Halaman review draft knowledge.
- Approve draft menjadi knowledge aktif.
- Reject/arsip draft.
- Sanitasi PII dasar.

### Kenapa Manual Dulu

- Mengontrol biaya token provider AI.
- Menghindari banjir draft dari chat tidak penting.
- Memberi kesempatan admin menilai kualitas hasil ekstraksi.
- Mengurangi risiko AI belajar dari informasi yang salah.

## Fase 2: Kualitas dan Deduplication

Fase kedua dilakukan setelah pola review stabil.

### Fitur

- Peringatan knowledge duplikat.
- Opsi merge/update knowledge existing.
- Scoring retrieval knowledge yang lebih baik.
- Filter draft berdasarkan customer, instansi, confidence, dan status.
- Template prompt ekstraksi yang lebih ketat berdasarkan hasil evaluasi.

## Fase 3: Otomatisasi Terbatas

Fase ketiga hanya jika fase 1-2 sudah aman.

### Fitur

- Job terjadwal untuk chat resolved/closed.
- Batas maksimal draft per hari.
- Minimum confidence score.
- Queue job agar UI tidak lambat.
- Dashboard kualitas draft.

## Fase 4: Vector Search Opsional

Fase ini opsional jika jumlah knowledge makin besar.

### Fitur

- Embedding untuk `MPengetahuan`.
- Similarity search untuk retrieval yang lebih akurat.
- Similarity search untuk deduplication.
- Rebuild embedding saat knowledge berubah.

### Catatan

Fase ini tidak wajib di awal karena menambah dependency, biaya, dan kompleksitas deployment.

## Desain Database

### Tabel Baru: `TAiDraftPengetahuan`

Kolom rekomendasi:

- `Id` uniqueidentifier primary key.
- `IdChat` uniqueidentifier nullable.
- `IdCustomer` uniqueidentifier nullable.
- `IdInstansi` uniqueidentifier nullable.
- `IdPengetahuan` uniqueidentifier nullable.
- `JudulDraft` nvarchar(255).
- `IsiDraft` nvarchar(max).
- `TagDraft` nvarchar(500) nullable.
- `KategoriDraft` nvarchar(100) nullable.
- `RingkasanSumber` nvarchar(max) nullable.
- `CuplikanSumberDisanitasi` nvarchar(max) nullable.
- `ConfidenceScore` decimal(5,2) nullable.
- `StatusReview` nvarchar(30), default `Draft`.
- `CatatanReviewer` nvarchar(max) nullable.
- `DibuatOlehAi` bit default 1.
- `DibuatOleh` uniqueidentifier nullable.
- `DireviewOleh` uniqueidentifier nullable.
- `TglReview` datetime2 nullable.
- `TglBuat` datetime2.
- `TglEdit` datetime2 nullable.

## Desain Service

### Service Baru

File: `src/app/Services/Ai/AiKnowledgeLearningService.php`

Tanggung jawab:

- Membaca chat dari `TChatD`.
- Membangun konteks aman untuk AI.
- Sanitasi data sensitif.
- Memanggil provider AI aktif.
- Parsing JSON response.
- Validasi hasil.
- Simpan draft knowledge.

### Sanitasi Minimal

Data yang perlu disamarkan:

- Email menjadi `[email]`.
- Nomor telepon panjang menjadi `[nomor]`.
- OTP/token/password menjadi `[rahasia]`.
- URL dengan token/query sensitif menjadi `[url]`.

## Prompt Ekstraksi

Prompt harus meminta AI:

- Hanya mengambil informasi reusable untuk CS.
- Tidak memasukkan data pribadi customer.
- Tidak membuat fakta baru.
- Mengembalikan JSON valid.
- Mengosongkan hasil jika tidak ada knowledge yang layak.

Contoh output JSON:

```json
{
  "layak": true,
  "judul": "Cara reset password pelanggan",
  "isi": "Jika pelanggan lupa password, arahkan pelanggan membuka menu Lupa Password, memasukkan email terdaftar, lalu mengikuti tautan reset yang dikirim melalui email.",
  "tag": "password, reset, login",
  "kategori": "Akun",
  "confidence": 86,
  "ringkasan_sumber": "CS menjelaskan prosedur reset password dan customer berhasil mengikuti instruksi."
}
```

Jika tidak layak:

```json
{
  "layak": false,
  "alasan": "Percakapan hanya berisi sapaan dan ucapan terima kasih."
}
```

## Desain UI

### Tombol Chat

Lokasi prioritas:

- `InboxWhatsapp`: untuk chat aktif.
- `ViewChatSession`: untuk histori/detail chat.
- `Ticketing`: opsional setelah struktur referensi chat dipastikan.

Label: **Buat Draft Knowledge**.

Perilaku:

- Klik tombol menjalankan ekstraksi.
- Sukses menampilkan notifikasi dan ID/link draft.
- Gagal menampilkan alasan ramah.

### Halaman Draft Knowledge

Menu: **Draft Knowledge AI**.

Fitur:

- Table draft.
- Badge status.
- Filter status.
- Preview sumber tersanitasi.
- Edit draft.
- Approve ke knowledge base.
- Reject.
- Perlu revisi.
- Arsip.

## Permission

Rekomendasi awal:

- Pakai `knowledge.view` untuk melihat draft.
- Pakai `knowledge.manage` untuk membuat, mengedit, approve, reject, dan arsip draft.

Permission baru `knowledge.learn` bisa ditambahkan jika ingin membedakan user yang boleh membuat draft dari user yang boleh mengelola knowledge.

## Dampak ke Auto Reply

Auto-reply tidak perlu memakai draft langsung.

Auto-reply tetap hanya membaca `MPengetahuan` aktif. Ini menjaga agar AI tidak menjawab berdasarkan draft yang belum divalidasi.

Peningkatan kecil yang direkomendasikan:

- Bobot match judul lebih tinggi.
- Bobot match tag lebih tinggi.
- Minimum score agar knowledge tidak salah masuk prompt.
- Batas total panjang knowledge agar prompt tidak boros token.

## Risiko Operasional

### Risiko: Draft Salah

Mitigasi: wajib review manusia.

### Risiko: PII Tersimpan

Mitigasi: sanitasi sebelum simpan dan reviewer wajib cek.

### Risiko: Biaya AI Naik

Mitigasi: trigger manual pada fase awal dan batas pesan yang dianalisis.

### Risiko: Duplikat Knowledge

Mitigasi: warning duplikat berdasarkan judul/tag, lalu vector search di fase lanjut.

### Risiko: Jawaban Auto Reply Salah

Mitigasi: hanya knowledge approved yang dipakai auto-reply.

## Urutan Implementasi Rekomendasi

1. Buat migration dan model `TAiDraftPengetahuan`.
2. Buat service ekstraksi dan sanitasi.
3. Buat resource review draft knowledge.
4. Tambahkan tombol manual di `ViewChatSession`.
5. Tambahkan tombol manual di `InboxWhatsapp`.
6. Tambahkan approve ke `MPengetahuan`.
7. Perbaiki retrieval scoring `AiAutoReplyService`.
8. Tambahkan localization.
9. Test manual dengan chat sampel.
10. Baru evaluasi otomasi/job.

## Checklist Review Sebelum Coding

- Apakah nama menu **Draft Knowledge AI** sudah cocok?
- Apakah cukup memakai permission `knowledge.manage`?
- Apakah tombol awal cukup di histori/detail chat dulu?
- Apakah draft perlu update knowledge existing, atau create baru saja di fase pertama?
- Apakah provider AI aktif boleh dipakai untuk ekstraksi, atau perlu setting provider khusus learning?

## Rekomendasi Final

Mulai dari fase 1. Jangan langsung fine-tuning dan jangan langsung auto-learn. Dengan struktur existing, cara terbaik adalah memperkuat Knowledge Base AI dari chat customer melalui draft, review, lalu approve.

## Performance Strategy untuk Knowledge Besar

### Masalah Utama

Jika knowledge base sudah banyak, lambatnya AI biasanya disebabkan oleh prompt terlalu besar, bukan sekadar koneksi internet. Semakin banyak knowledge dikirim ke provider AI, semakin besar token input, semakin mahal, dan semakin lama respons.

### Strategi Ringan yang Direkomendasikan

Jangan kirim semua knowledge. Sistem harus melakukan retrieval lokal dulu.

Target fase awal:

- Kandidat database maksimal 30-50 row.
- Knowledge masuk prompt maksimal 3-5 item.
- Isi per knowledge maksimal 700-900 karakter.
- Total knowledge context maksimal 2500-3500 karakter.
- Draft yang belum approved tidak pernah masuk prompt.

### Perubahan yang Disarankan

Tambahkan metadata ringan di `MPengetahuan`:

- `SearchKeywords`: kata kunci pendek yang mudah dicari.
- `PrioritasAi`: prioritas knowledge penting.
- `TerakhirDipakaiAi`: audit kapan terakhir masuk prompt.
- `JumlahDipakaiAi`: statistik pemakaian.

Dengan ini, query bisa mencari kandidat kecil dari tag/keyword/prioritas, bukan membaca semua knowledge.

### Algoritma Retrieval Ringan

1. Ambil pesan customer terbaru.
2. Ekstrak token penting.
3. Query `MPengetahuan` aktif berdasarkan token di judul/tag/keyword.
4. Limit kandidat 30-50 row.
5. Score lokal dengan bobot:
   - judul paling tinggi,
   - tag tinggi,
   - keyword sedang,
   - isi rendah.
6. Ambil top 3-5.
7. Potong isi agar prompt kecil.
8. Cache hasil beberapa menit untuk topik berulang.

### Kapan Perlu Embedding

Embedding belum wajib di awal. Embedding dipakai jika:

- Knowledge aktif sudah ratusan/ribuan.
- Keyword/tag tidak cukup menemukan sinonim.
- Banyak pertanyaan customer bahasanya tidak sama dengan knowledge.
- Latency retrieval keyword masih aman tetapi relevansi kurang.

Jika nanti pakai embedding, tetap disarankan hybrid: keyword prefilter dulu, baru similarity. Ini lebih ringan daripada membandingkan semua vector.

### Kesimpulan Revisi

Implementasi fase 1 harus mencakup dua hal sekaligus:

1. Draft-review-approve agar AI belajar aman dari chat.
2. Retrieval ringan agar knowledge besar tidak memperlambat koneksi AI.

## Opsi Per Chat untuk CS Online

CS yang sedang membuka chat bisa memilih mode knowledge AI khusus untuk chat tersebut:

- **Ringan / Cepat**: default, paling aman untuk performa.
- **All Knowledge**: mengambil lebih banyak knowledge approved, dipakai hanya jika kasus sulit.
- **Tanpa Knowledge**: AI menjawab hanya dari prompt dan riwayat chat.

Pilihan ini disimpan di `TChat`, sehingga hanya berlaku untuk chat itu dan tidak mengubah setting global AI Agent.

## Addendum 2026-06-28 — Integrasi VPoint Assistant Internal

### Scope Tambahan
- VPoint Assistant dapat membuat `TAiDraftPengetahuan` dari jawaban AI internal melalui tombol draft pada bubble assistant.
- Draft memakai `HashKonten` untuk deduplikasi agar jawaban yang sama tidak membuat draft berulang.
- Draft dari assistant tetap masuk alur review existing: reviewer dapat approve menjadi `MPengetahuan`, revisi, reject, atau archive.
- Mode `All Knowledge` membantu assistant menyusun draft berbasis knowledge aktif; mode `Tanpa Knowledge` membantu membuat draft dari file/log/percakapan user tanpa retrieval knowledge.
- Attach file teks menjadi sumber tambahan untuk draft, tetapi file non-teks hanya dicatat sebagai metadata karena ekstraksi konten belum otomatis.

### Acceptance Criteria
- User dengan `knowledge.manage` melihat tombol buat draft pada jawaban VPoint Assistant.
- Klik tombol draft membuat record `TAiDraftPengetahuan` status `Draft`.
- Draft yang sama tidak diduplikasi bila tombol ditekan ulang.
- Draft dapat diproses di resource Draft Pengetahuan AI existing.
- Label UI tersedia dalam Bahasa Indonesia dan English.
