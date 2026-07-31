## Requirements

### Requirement: Sound Toggle Visual Consistency

Sistem SHALL menampilkan hanya satu icon speaker pada tombol toggle sound di halaman Inbox WhatsApp, sesuai dengan state aktif/non-aktif notifikasi suara.

#### Scenario: Sound aktif menampilkan icon wave

- **GIVEN** user berada di halaman Inbox WhatsApp dan localStorage wacs_sound bernilai "true" atau tidak ada (default true)
- **WHEN** halaman dimuat
- **THEN** tombol toggle sound menampilkan icon heroicon-o-speaker-wave saja
- **AND** text label menunjukkan "{{ __('ui.pages.inbox.sound_on') }}"

#### Scenario: Sound non-aktif menampilkan icon x-mark

- **GIVEN** user berada di halaman Inbox WhatsApp dan localStorage wacs_sound bernilai "false"
- **WHEN** halaman dimuat
- **THEN** tombol toggle sound menampilkan icon heroicon-o-speaker-x-mark saja
- **AND** text label menunjukkan "{{ __('ui.pages.inbox.sound_off') }}"

#### Scenario: Toggle sound mengubah icon secara instan

- **GIVEN** user melihat tombol toggle sound dengan salah satu icon
- **WHEN** user mengklik tombol tersebut
- **THEN** icon berubah ke state berlawanan tanpa delay visual atau double rendering
- **AND** localStorage wacs_sound diperbarui sesuai state baru
- **AND** tidak ada icon lain yang muncul bersamaan selama transisi

### Requirement: Filter Tab Selection State Visibility

Sistem SHALL menunjukkan state selected yang jelas pada radio button filter [WhatsApp Asli] (Semua/Grup/Pribadi) setelah page load dan setiap interaksi user.

#### Scenario: Default filter selected saat page load

- **GIVEN** user membuka halaman Inbox WhatsApp untuk pertama kali
- **WHEN** halaman selesai dimuat
- **THEN** salah satu tab filter (Semua/Grup/Pribadi) menunjukkan visual selected state (background putih, shadow, warna primary)
- **AND** property Livewire $filterType memiliki nilai default yang konsisten dengan tab yang terpilih

#### Scenario: Klik tab mengubah selected state

- **GIVEN** user melihat tab filter dengan satu tab dalam state selected
- **WHEN** user mengklik tab lain (misal dari "Semua" ke "Grup")
- **THEN** tab yang diklik segera menunjukkan visual selected state
- **AND** tab sebelumnya kehilangan visual selected state
- **AND** daftar chat di bawahnya difilter sesuai jenis yang dipilih

#### Scenario: Selected state persist setelah refresh

- **GIVEN** user telah memilih tab filter tertentu (bukan default)
- **WHEN** user me-refresh halaman
- **THEN** tab yang terakhir dipilih tetap menunjukkan selected state jika state disimpan di session/Livewire
- **OR** kembali ke default jika tidak ada persistence mechanism

### Requirement: Chat Loading Performance Optimization

Sistem SHALL memuat riwayat chat history dalam waktu kurang dari 1 detik setelah user mengklik kontak di tab [WhatsApp Asli], dengan loading indicator yang terlihat selama proses.

#### Scenario: Chat pribadi loaded cepat

- **GIVEN** user berada di halaman Inbox WhatsApp dengan tab [WhatsApp Asli] aktif
- **WHEN** user mengklik kontak pribadi dari daftar chat
- **THEN** loading indicator/spinner muncul dalam <100ms
- **AND** riwayat chat ditampilkan dalam <1 detik untuk kontak dengan <50 pesan terakhir
- **AND** tidak ada blocking UI thread selama proses loading

#### Scenario: Chat grup loaded cepat

- **GIVEN** user berada di halaman Inbox WhatsApp dengan tab [WhatsApp Asli] aktif
- **WHEN** user mengklik kontak grup dari daftar chat
- **THEN** loading indicator/spinner muncul dalam <100ms
- **AND** riwayat chat ditampilkan dalam <1 detik untuk grup dengan <50 pesan terakhir
- **AND** profile foto grup dan nama grup ditampilkan dengan benar menggunakan raw group JID

#### Scenario: Loading indicator terlihat selama fetch

- **GIVEN** user mengklik kontak dengan chat history besar (>100 pesan)
- **WHEN** sistem sedang mengambil data dari database
- **THEN** loading indicator/spinner tetap terlihat sampai semua data siap dirender
- **AND** user tidak dapat melakukan aksi lain (klik kontak lain) sampai loading selesai atau timeout

#### Scenario: Error handling saat chat gagal dimuat

- **GIVEN** user mengklik kontak tetapi terjadi error saat fetch chat history
- **WHEN** request gagal atau timeout (>5 detik)
- **THEN** loading indicator hilang
- **AND** pesan error ditampilkan di area chat (misal: "Gagal memuat riwayat chat")
- **AND** user dapat mencoba lagi dengan mengklik kontak yang sama

### Requirement: No N+1 Query Problem in Chat History Loading

Sistem SHALL menggunakan eager loading untuk semua relasi yang diperlukan saat memuat chat history, menghindari query N+1 yang menyebabkan performa lambat.

#### Scenario: Single query untuk chat messages dengan relasi

- **GIVEN** user mengklik kontak dengan 50 pesan terakhir
- **WHEN** method .loadHistoryChats() dipanggil
- **THEN** jumlah query SQL yang dieksekusi tidak lebih dari 3-5 query (main chat query + eager loaded relations)
- **AND** tidak ada query tambahan per-message untuk mengambil data relasi (sender, media, mapping)

#### Scenario: Eager loaded WahaIdentitySnapshot

- **GIVEN** chat history memerlukan informasi identitas WAHA (nama, avatar, group info)
- **WHEN** sistem memuat chat messages
- **THEN** data wahaIdentitySnapshot di-load via eager loading (with()) bukan lazy loading
- **AND** tidak ada query tambahan per-message untuk mengambil snapshot identity

### Requirement: Blocking Operations Moved to Background

Sistem SHALL tidak melakukan synchronous HTTP request ke WAHA API di dalam flow .selectChat() atau .loadHistoryChats() yang memblok UI thread.

#### Scenario: Profile fetch dilakukan async

- **GIVEN** user mengklik kontak yang memerlukan fetch profile dari WAHA
- **WHEN** sistem perlu mengambil data profile terbaru
- **THEN** fetch profile dilakukan via background job/queue atau async call
- **AND** UI tetap responsif dan menampilkan cached data sementara
- **AND** profile di-update setelah background job selesai tanpa reload halaman penuh
