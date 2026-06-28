SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/*
    Aplikasi: VPoint Care
    WACS = WhatsApp Customer Service
    Target: SQL Server
    PK: uniqueidentifier DEFAULT NEWSEQUENTIALID()
    Catatan:
    - Master data diawali M dan memiliki NonAktif.
    - Transaksi data diawali T.
    - Semua tabel memiliki TglBuat, DibuatOleh, TglEdit, DieditOleh.
    - Audit user tidak dibuat FK agar data historis tetap aman saat user berubah.
*/

CREATE TABLE MPeran (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPeran_Id DEFAULT NEWSEQUENTIALID(),
    KodePeran varchar(50) NOT NULL,
    NamaPeran varchar(100) NOT NULL,
    Keterangan varchar(255) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MPeran_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPeran_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPeran PRIMARY KEY (Id),
    CONSTRAINT UQ_MPeran_KodePeran UNIQUE (KodePeran)
);
GO

CREATE TABLE MHakAkses (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MHakAkses_Id DEFAULT NEWSEQUENTIALID(),
    IdHakAkses uniqueidentifier NULL,
    KodeHakAkses varchar(100) NULL,
    NamaHakAkses varchar(150) NOT NULL,
    NamaHakAksesId varchar(150) NULL,
    NamaHakAksesEn varchar(150) NULL,
    Modul varchar(100) NOT NULL,
    ModulId varchar(100) NULL,
    ModulEn varchar(100) NULL,
    Keterangan varchar(255) NULL,
    KeteranganId varchar(255) NULL,
    KeteranganEn varchar(255) NULL,
    SortOrder int NULL,
    IconString varchar(100) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MHakAkses_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MHakAkses_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MHakAkses PRIMARY KEY (Id),
    CONSTRAINT FK_MHakAkses_IdHakAkses FOREIGN KEY (IdHakAkses) REFERENCES MHakAkses(Id)
);
GO

CREATE UNIQUE INDEX UX_MHakAkses_KodeHakAkses_NotNull
    ON MHakAkses (KodeHakAkses)
    WHERE KodeHakAkses IS NOT NULL;
GO

CREATE TABLE MPeranHakAkses (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPeranHakAkses_Id DEFAULT NEWSEQUENTIALID(),
    IdPeran uniqueidentifier NOT NULL,
    IdHakAkses uniqueidentifier NOT NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MPeranHakAkses_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPeranHakAkses_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPeranHakAkses PRIMARY KEY (Id),
    CONSTRAINT FK_MPeranHakAkses_MPeran FOREIGN KEY (IdPeran) REFERENCES MPeran(Id),
    CONSTRAINT FK_MPeranHakAkses_MHakAkses FOREIGN KEY (IdHakAkses) REFERENCES MHakAkses(Id),
    CONSTRAINT UQ_MPeranHakAkses UNIQUE (IdPeran, IdHakAkses)
);
GO

CREATE TABLE MPengguna (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPengguna_Id DEFAULT NEWSEQUENTIALID(),
    IdPeran uniqueidentifier NOT NULL,
    NamaPengguna varchar(150) NOT NULL,
    Email varchar(150) NOT NULL,
    Password varchar(255) NOT NULL,
    NomorWhatsappInternal varchar(30) NULL,
    FotoProfilPath nvarchar(500) NULL,
    Jabatan varchar(100) NULL,
    RememberToken varchar(100) NULL,
    EmailTerverifikasiPada datetime2 NULL,
    LoginTerakhirPada datetime2 NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MPengguna_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPengguna_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPengguna PRIMARY KEY (Id),
    CONSTRAINT FK_MPengguna_MPeran FOREIGN KEY (IdPeran) REFERENCES MPeran(Id),
    CONSTRAINT UQ_MPengguna_Email UNIQUE (Email)
);
GO

CREATE TABLE MInstansi (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MInstansi_Id DEFAULT NEWSEQUENTIALID(),
    KodeInstansi varchar(50) NOT NULL,
    NamaInstansi varchar(200) NOT NULL,
    Alamat varchar(500) NULL,
    Kota varchar(100) NULL,
    Provinsi varchar(100) NULL,
    Negara varchar(100) NULL,
    KodePos varchar(20) NULL,
    Telepon varchar(50) NULL,
    Email varchar(150) NULL,
    Website varchar(200) NULL,
    SumberData varchar(50) NULL,
    IdExternal varchar(100) NULL,
    TglSinkronTerakhir datetime2 NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MInstansi_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MInstansi_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MInstansi PRIMARY KEY (Id),
    CONSTRAINT UQ_MInstansi_KodeInstansi UNIQUE (KodeInstansi)
);
GO

CREATE TABLE MCustomer (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MCustomer_Id DEFAULT NEWSEQUENTIALID(),
    IdInstansi uniqueidentifier NULL,
    KodeCustomer varchar(50) NOT NULL,
    NamaCustomer varchar(200) NOT NULL,
    Email varchar(150) NULL,
    Telepon varchar(50) NULL,
    Jabatan varchar(100) NULL,
    Catatan varchar(1000) NULL,
    SumberData varchar(50) NULL,
    IdExternal varchar(100) NULL,
    TglSinkronTerakhir datetime2 NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MCustomer_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MCustomer_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MCustomer PRIMARY KEY (Id),
    CONSTRAINT FK_MCustomer_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
    CONSTRAINT UQ_MCustomer_KodeCustomer UNIQUE (KodeCustomer)
);
GO

CREATE TABLE MNomorWhatsapp (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MNomorWhatsapp_Id DEFAULT NEWSEQUENTIALID(),
    IdCustomer uniqueidentifier NULL,
    IdInstansi uniqueidentifier NULL,
    NomorWhatsapp varchar(30) NOT NULL,
    NamaKontak varchar(150) NULL,
    JabatanKontak varchar(100) NULL,
    NomorUtama bit NOT NULL CONSTRAINT DF_MNomorWhatsapp_NomorUtama DEFAULT 0,
    Terverifikasi bit NOT NULL CONSTRAINT DF_MNomorWhatsapp_Terverifikasi DEFAULT 0,
    SumberData varchar(50) NULL,
    IdExternal varchar(100) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MNomorWhatsapp_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MNomorWhatsapp_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MNomorWhatsapp PRIMARY KEY (Id),
    CONSTRAINT FK_MNomorWhatsapp_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
    CONSTRAINT FK_MNomorWhatsapp_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
    CONSTRAINT UQ_MNomorWhatsapp_NomorWhatsapp UNIQUE (NomorWhatsapp)
);
GO

CREATE TABLE MGrupWhatsapp (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MGrupWhatsapp_Id DEFAULT NEWSEQUENTIALID(),
    IdInstansi uniqueidentifier NOT NULL,
    KodeGrup varchar(50) NOT NULL,
    NamaGrup varchar(200) NOT NULL,
    IdGrupWaha varchar(200) NULL,
    NomorGrupWhatsapp varchar(100) NULL,
    Deskripsi varchar(500) NULL,
    SumberData varchar(50) NULL,
    IdExternal varchar(100) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MGrupWhatsapp_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MGrupWhatsapp_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MGrupWhatsapp PRIMARY KEY (Id),
    CONSTRAINT FK_MGrupWhatsapp_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
    CONSTRAINT UQ_MGrupWhatsapp_KodeGrup UNIQUE (KodeGrup)
);
GO

CREATE TABLE MAnggotaGrupWhatsapp (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MAnggotaGrupWhatsapp_Id DEFAULT NEWSEQUENTIALID(),
    IdGrupWhatsapp uniqueidentifier NOT NULL,
    IdNomorWhatsapp uniqueidentifier NOT NULL,
    IdCustomer uniqueidentifier NULL,
    PeranAnggota varchar(100) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MAnggotaGrupWhatsapp_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MAnggotaGrupWhatsapp_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MAnggotaGrupWhatsapp PRIMARY KEY (Id),
    CONSTRAINT FK_MAnggotaGrupWhatsapp_MGrupWhatsapp FOREIGN KEY (IdGrupWhatsapp) REFERENCES MGrupWhatsapp(Id),
    CONSTRAINT FK_MAnggotaGrupWhatsapp_MNomorWhatsapp FOREIGN KEY (IdNomorWhatsapp) REFERENCES MNomorWhatsapp(Id),
    CONSTRAINT FK_MAnggotaGrupWhatsapp_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
    CONSTRAINT UQ_MAnggotaGrupWhatsapp UNIQUE (IdGrupWhatsapp, IdNomorWhatsapp)
);
GO

CREATE TABLE MProdukCustomer (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MProdukCustomer_Id DEFAULT NEWSEQUENTIALID(),
    IdCustomer uniqueidentifier NULL,
    IdInstansi uniqueidentifier NULL,
    KodeProduk varchar(50) NOT NULL,
    NamaProduk varchar(150) NOT NULL,
    Keterangan varchar(500) NULL,
    TglMulai datetime2 NULL,
    TglBerakhir datetime2 NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MProdukCustomer_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MProdukCustomer_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MProdukCustomer PRIMARY KEY (Id),
    CONSTRAINT FK_MProdukCustomer_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
    CONSTRAINT FK_MProdukCustomer_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id)
);
GO

CREATE TABLE MStatusChat (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MStatusChat_Id DEFAULT NEWSEQUENTIALID(),
    KodeStatusChat varchar(50) NOT NULL,
    NamaStatusChat varchar(100) NOT NULL,
    Urutan int NOT NULL CONSTRAINT DF_MStatusChat_Urutan DEFAULT 0,
    Warna varchar(30) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MStatusChat_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MStatusChat_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MStatusChat PRIMARY KEY (Id),
    CONSTRAINT UQ_MStatusChat_KodeStatusChat UNIQUE (KodeStatusChat)
);
GO

CREATE TABLE MKategoriTicket (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MKategoriTicket_Id DEFAULT NEWSEQUENTIALID(),
    KodeKategori varchar(50) NOT NULL,
    NamaKategori varchar(150) NOT NULL,
    Keterangan varchar(500) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MKategoriTicket_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MKategoriTicket_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MKategoriTicket PRIMARY KEY (Id),
    CONSTRAINT UQ_MKategoriTicket_KodeKategori UNIQUE (KodeKategori)
);
GO

CREATE TABLE MPrioritasTicket (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPrioritasTicket_Id DEFAULT NEWSEQUENTIALID(),
    KodePrioritas varchar(50) NOT NULL,
    NamaPrioritas varchar(100) NOT NULL,
    Urutan int NOT NULL CONSTRAINT DF_MPrioritasTicket_Urutan DEFAULT 0,
    BatasSlaMenit int NULL,
    Warna varchar(30) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MPrioritasTicket_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPrioritasTicket_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPrioritasTicket PRIMARY KEY (Id),
    CONSTRAINT UQ_MPrioritasTicket_KodePrioritas UNIQUE (KodePrioritas)
);
GO

CREATE TABLE MStatusTicket (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MStatusTicket_Id DEFAULT NEWSEQUENTIALID(),
    KodeStatusTicket varchar(50) NOT NULL,
    NamaStatusTicket varchar(100) NOT NULL,
    Urutan int NOT NULL CONSTRAINT DF_MStatusTicket_Urutan DEFAULT 0,
    StatusFinal bit NOT NULL CONSTRAINT DF_MStatusTicket_StatusFinal DEFAULT 0,
    Warna varchar(30) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MStatusTicket_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MStatusTicket_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MStatusTicket PRIMARY KEY (Id),
    CONSTRAINT UQ_MStatusTicket_KodeStatusTicket UNIQUE (KodeStatusTicket)
);
GO

CREATE TABLE MSesiWhatsapp (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MSesiWhatsapp_Id DEFAULT NEWSEQUENTIALID(),
    KodeSesi varchar(50) NOT NULL,
    NamaSesi varchar(150) NOT NULL,
    BaseUrlWaha varchar(255) NOT NULL,
    ApiKey varchar(255) NULL,
    NomorTerhubung varchar(30) NULL,
    StatusSesi varchar(50) NOT NULL CONSTRAINT DF_MSesiWhatsapp_StatusSesi DEFAULT 'TidakAktif',
    WebhookToken varchar(255) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MSesiWhatsapp_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MSesiWhatsapp_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MSesiWhatsapp PRIMARY KEY (Id),
    CONSTRAINT UQ_MSesiWhatsapp_KodeSesi UNIQUE (KodeSesi)
);
GO

CREATE TABLE MEndpointIntegrasi (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MEndpointIntegrasi_Id DEFAULT NEWSEQUENTIALID(),
    KodeEndpoint varchar(100) NOT NULL,
    NamaEndpoint varchar(150) NOT NULL,
    UrlEndpoint varchar(500) NOT NULL,
    MetodeHttp varchar(10) NOT NULL,
    HeaderJson nvarchar(max) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MEndpointIntegrasi_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MEndpointIntegrasi_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MEndpointIntegrasi PRIMARY KEY (Id),
    CONSTRAINT UQ_MEndpointIntegrasi_KodeEndpoint UNIQUE (KodeEndpoint)
);
GO

CREATE TABLE MAiProvider (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MAiProvider_Id DEFAULT NEWSEQUENTIALID(),
    KodeProvider varchar(50) NOT NULL,
    NamaProvider varchar(100) NOT NULL,
    BaseUrl varchar(255) NULL,
    ApiKeyTerenkripsi varchar(1000) NULL,
    ModelDefault varchar(100) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MAiProvider_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MAiProvider_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MAiProvider PRIMARY KEY (Id),
    CONSTRAINT UQ_MAiProvider_KodeProvider UNIQUE (KodeProvider)
);
GO

CREATE TABLE MHariLibur (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MHariLibur_Id DEFAULT NEWSEQUENTIALID(),
    TanggalLibur date NOT NULL,
    NamaHariLibur varchar(200) NOT NULL,
    Keterangan varchar(1000) NULL,
    BerlakuTahunan bit NOT NULL CONSTRAINT DF_MHariLibur_BerlakuTahunan DEFAULT 0,
    NonAktif bit NOT NULL CONSTRAINT DF_MHariLibur_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MHariLibur_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MHariLibur PRIMARY KEY (Id)
);
GO

CREATE TABLE MPengaturanAi (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPengaturanAi_Id DEFAULT NEWSEQUENTIALID(),
    KodePengaturan varchar(50) NOT NULL,
    NamaPengaturan varchar(100) NOT NULL,
    AutoReplyAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyAktif DEFAULT 0,
    AutoReplyDiluarJamKerja bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyDiluarJamKerja DEFAULT 1,
    AutoReplyHariLibur bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyHariLibur DEFAULT 1,
    AutoReplyJamKerjaSapaan bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyJamKerjaSapaan DEFAULT 1,
    AutoReplyJamKerjaBerlanjut bit NOT NULL CONSTRAINT DF_MPengaturanAi_AutoReplyJamKerjaBerlanjut DEFAULT 0,
    JamKerjaMulai time(0) NOT NULL CONSTRAINT DF_MPengaturanAi_JamKerjaMulai DEFAULT '08:00',
    JamKerjaSelesai time(0) NOT NULL CONSTRAINT DF_MPengaturanAi_JamKerjaSelesai DEFAULT '17:00',
    HariKerja varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_HariKerja DEFAULT '1,2,3,4,5',
    ZonaWaktu varchar(100) NOT NULL CONSTRAINT DF_MPengaturanAi_ZonaWaktu DEFAULT 'Asia/Jakarta',
    ProviderAi varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_ProviderAi DEFAULT 'OpenAI',
    ModelAi varchar(100) NULL,
    BaseUrl varchar(255) NULL,
    ApiKeyTerenkripsi nvarchar(max) NULL,
    PromptSistem nvarchar(max) NULL,
    TemplateDiluarJamKerja nvarchar(max) NULL,
    TemplateHariLibur nvarchar(max) NULL,
    TemplateJamKerjaSapaan nvarchar(max) NULL,
    TemplateFallback nvarchar(max) NULL,
    NotifikasiChatBelumTerbalasAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_NotifikasiChatBelumTerbalasAktif DEFAULT 1,
    MenitTungguNotifikasi int NOT NULL CONSTRAINT DF_MPengaturanAi_MenitTungguNotifikasi DEFAULT 10,
    JedaNotifikasiMenit int NOT NULL CONSTRAINT DF_MPengaturanAi_JedaNotifikasiMenit DEFAULT 30,
    KodePeranPenerimaNotifikasi varchar(200) NOT NULL CONSTRAINT DF_MPengaturanAi_KodePeranPenerimaNotifikasi DEFAULT 'ADMIN,SUPERVISOR_CS,CS',
    TemplateNotifikasiChatBelumTerbalas nvarchar(max) NULL,
    BatasRiwayatPesan int NOT NULL CONSTRAINT DF_MPengaturanAi_BatasRiwayatPesan DEFAULT 8,
    KirimKeWaha bit NOT NULL CONSTRAINT DF_MPengaturanAi_KirimKeWaha DEFAULT 0,
    ModeKirim varchar(50) NOT NULL CONSTRAINT DF_MPengaturanAi_ModeKirim DEFAULT 'DraftLokal',
    NonAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPengaturanAi_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPengaturanAi PRIMARY KEY (Id),
    CONSTRAINT UQ_MPengaturanAi_KodePengaturan UNIQUE (KodePengaturan)
);
GO

CREATE TABLE MPengetahuan (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_MPengetahuan_Id DEFAULT NEWSEQUENTIALID(),
    KodePengetahuan varchar(50) NOT NULL,
    JudulPengetahuan varchar(200) NOT NULL,
    IsiPengetahuan nvarchar(max) NOT NULL,
    Tag varchar(500) NULL,
    NonAktif bit NOT NULL CONSTRAINT DF_MPengetahuan_NonAktif DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_MPengetahuan_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_MPengetahuan PRIMARY KEY (Id),
    CONSTRAINT UQ_MPengetahuan_KodePengetahuan UNIQUE (KodePengetahuan)
);
GO

CREATE TABLE TLogAktivitas (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TLogAktivitas_Id DEFAULT NEWSEQUENTIALID(),
    IdPengguna uniqueidentifier NULL,
    Modul varchar(100) NOT NULL,
    Aksi varchar(100) NOT NULL,
    Keterangan varchar(1000) NULL,
    IpAddress varchar(50) NULL,
    UserAgent varchar(500) NULL,
    DataSebelumJson nvarchar(max) NULL,
    DataSesudahJson nvarchar(max) NULL,
    TglAktivitas datetime2 NOT NULL CONSTRAINT DF_TLogAktivitas_TglAktivitas DEFAULT SYSDATETIME(),
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TLogAktivitas_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TLogAktivitas PRIMARY KEY (Id)
);
GO

CREATE TABLE TLogError (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TLogError_Id DEFAULT NEWSEQUENTIALID(),
    LevelError varchar(50) NOT NULL,
    PesanError nvarchar(max) NOT NULL,
    FileError varchar(500) NULL,
    BarisError int NULL,
    StackTrace nvarchar(max) NULL,
    ContextJson nvarchar(max) NULL,
    TglError datetime2 NOT NULL CONSTRAINT DF_TLogError_TglError DEFAULT SYSDATETIME(),
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TLogError_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TLogError PRIMARY KEY (Id)
);
GO

CREATE TABLE TLogIntegrasi (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TLogIntegrasi_Id DEFAULT NEWSEQUENTIALID(),
    IdEndpointIntegrasi uniqueidentifier NULL,
    KodeIntegrasi varchar(100) NOT NULL,
    UrlEndpoint varchar(500) NOT NULL,
    MetodeHttp varchar(10) NOT NULL,
    RequestJson nvarchar(max) NULL,
    ResponseJson nvarchar(max) NULL,
    StatusHttp int NULL,
    Berhasil bit NOT NULL CONSTRAINT DF_TLogIntegrasi_Berhasil DEFAULT 0,
    PesanError nvarchar(max) NULL,
    TglRequest datetime2 NOT NULL CONSTRAINT DF_TLogIntegrasi_TglRequest DEFAULT SYSDATETIME(),
    TglResponse datetime2 NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TLogIntegrasi_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TLogIntegrasi PRIMARY KEY (Id),
    CONSTRAINT FK_TLogIntegrasi_MEndpointIntegrasi FOREIGN KEY (IdEndpointIntegrasi) REFERENCES MEndpointIntegrasi(Id)
);
GO

CREATE TABLE TLogWebhookWaha (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TLogWebhookWaha_Id DEFAULT NEWSEQUENTIALID(),
    IdSesiWhatsapp uniqueidentifier NULL,
    JenisEvent varchar(100) NOT NULL,
    PayloadJson nvarchar(max) NOT NULL,
    TglDiterima datetime2 NOT NULL CONSTRAINT DF_TLogWebhookWaha_TglDiterima DEFAULT SYSDATETIME(),
    SudahDiproses bit NOT NULL CONSTRAINT DF_TLogWebhookWaha_SudahDiproses DEFAULT 0,
    TglDiproses datetime2 NULL,
    PesanError nvarchar(max) NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TLogWebhookWaha_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TLogWebhookWaha PRIMARY KEY (Id),
    CONSTRAINT FK_TLogWebhookWaha_MSesiWhatsapp FOREIGN KEY (IdSesiWhatsapp) REFERENCES MSesiWhatsapp(Id)
);
GO

CREATE TABLE TChat (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TChat_Id DEFAULT NEWSEQUENTIALID(),
    IdSesiWhatsapp uniqueidentifier NOT NULL,
    IdStatusChat uniqueidentifier NULL,
    IdCustomer uniqueidentifier NULL,
    IdInstansi uniqueidentifier NULL,
    IdNomorWhatsapp uniqueidentifier NULL,
    IdGrupWhatsapp uniqueidentifier NULL,
    JenisChat varchar(30) NOT NULL CONSTRAINT DF_TChat_JenisChat DEFAULT 'Pribadi',
    NomorWhatsapp varchar(30) NOT NULL,
    NamaKontak varchar(150) NULL,
    NamaGrupWhatsapp varchar(200) NULL,
    IdWahaTerdeteksi varchar(200) NULL,
    NomorWhatsappTerdeteksi varchar(30) NULL,
    UrlFotoProfil nvarchar(1000) NULL,
    TglFotoProfilDiambil datetime2 NULL,
    Prioritas varchar(50) NOT NULL CONSTRAINT DF_TChat_Prioritas DEFAULT 'Normal',
    DitugaskanKepada uniqueidentifier NULL,
    DiambilOleh uniqueidentifier NULL,
    TglDiambil datetime2 NULL,
    TglChatTerakhir datetime2 NULL,
    TglDibalasTerakhir datetime2 NULL,
    JumlahPesanBelumDibaca int NOT NULL CONSTRAINT DF_TChat_JumlahPesanBelumDibaca DEFAULT 0,
    DitutupOleh uniqueidentifier NULL,
    TglDitutup datetime2 NULL,
    RingkasanAi nvarchar(max) NULL,
    AutoReplyAiAktif bit NOT NULL CONSTRAINT DF_TChat_AutoReplyAiAktif DEFAULT 0,
    AiSudahMenyapa bit NOT NULL CONSTRAINT DF_TChat_AiSudahMenyapa DEFAULT 0,
    ModeAutoReplyAi varchar(50) NOT NULL CONSTRAINT DF_TChat_ModeAutoReplyAi DEFAULT 'Default',
    TglAutoReplyAiTerakhir datetime2 NULL,
    TglNotifikasiBelumTerbalasTerakhir datetime2 NULL,
    JumlahNotifikasiBelumTerbalas int NOT NULL CONSTRAINT DF_TChat_JumlahNotifikasiBelumTerbalas DEFAULT 0,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TChat_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TChat PRIMARY KEY (Id),
    CONSTRAINT FK_TChat_MSesiWhatsapp FOREIGN KEY (IdSesiWhatsapp) REFERENCES MSesiWhatsapp(Id),
    CONSTRAINT FK_TChat_MStatusChat FOREIGN KEY (IdStatusChat) REFERENCES MStatusChat(Id),
    CONSTRAINT FK_TChat_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
    CONSTRAINT FK_TChat_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
    CONSTRAINT FK_TChat_MNomorWhatsapp FOREIGN KEY (IdNomorWhatsapp) REFERENCES MNomorWhatsapp(Id),
    CONSTRAINT FK_TChat_MGrupWhatsapp FOREIGN KEY (IdGrupWhatsapp) REFERENCES MGrupWhatsapp(Id)
);
GO

CREATE TABLE TChatD (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TChatD_Id DEFAULT NEWSEQUENTIALID(),
    IdChat uniqueidentifier NOT NULL,
    IdLogWebhookWaha uniqueidentifier NULL,
    IdPesanWaha varchar(200) NULL,
    ArahPesan varchar(20) NOT NULL,
    JenisPesan varchar(50) NOT NULL CONSTRAINT DF_TChatD_JenisPesan DEFAULT 'Teks',
    IsiPesan nvarchar(max) NULL,
    UrlMedia varchar(1000) NULL,
    NamaFileMedia varchar(255) NULL,
    TipeMime varchar(100) NULL,
    PayloadJson nvarchar(max) NULL,
    PengirimNomorWhatsapp varchar(30) NULL,
    PengirimNamaKontak varchar(150) NULL,
    DikirimOlehCustomer bit NOT NULL CONSTRAINT DF_TChatD_DikirimOlehCustomer DEFAULT 0,
    DihasilkanOlehAi bit NOT NULL CONSTRAINT DF_TChatD_DihasilkanOlehAi DEFAULT 0,
    IdAiRespon uniqueidentifier NULL,
    DibalasOleh uniqueidentifier NULL,
    TglPesan datetime2 NOT NULL,
    TglDikirim datetime2 NULL,
    TglDibaca datetime2 NULL,
    StatusKirim varchar(50) NULL,
    PesanError nvarchar(max) NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TChatD_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TChatD PRIMARY KEY (Id),
    CONSTRAINT FK_TChatD_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id),
    CONSTRAINT FK_TChatD_TLogWebhookWaha FOREIGN KEY (IdLogWebhookWaha) REFERENCES TLogWebhookWaha(Id)
);
GO

CREATE TABLE TChatDPenugasan (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TChatDPenugasan_Id DEFAULT NEWSEQUENTIALID(),
    IdChat uniqueidentifier NOT NULL,
    DitugaskanDari uniqueidentifier NULL,
    DitugaskanKepada uniqueidentifier NOT NULL,
    AlasanPenugasan varchar(500) NULL,
    TglPenugasan datetime2 NOT NULL CONSTRAINT DF_TChatDPenugasan_TglPenugasan DEFAULT SYSDATETIME(),
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TChatDPenugasan_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TChatDPenugasan PRIMARY KEY (Id),
    CONSTRAINT FK_TChatDPenugasan_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id)
);
GO

CREATE TABLE TChatDCatatanInternal (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TChatDCatatanInternal_Id DEFAULT NEWSEQUENTIALID(),
    IdChat uniqueidentifier NOT NULL,
    IsiCatatan nvarchar(max) NOT NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TChatDCatatanInternal_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TChatDCatatanInternal PRIMARY KEY (Id),
    CONSTRAINT FK_TChatDCatatanInternal_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id)
);
GO

CREATE TABLE TTicket (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TTicket_Id DEFAULT NEWSEQUENTIALID(),
    NomorTicket varchar(50) NOT NULL,
    IdChat uniqueidentifier NULL,
    IdCustomer uniqueidentifier NULL,
    IdInstansi uniqueidentifier NULL,
    IdKategoriTicket uniqueidentifier NULL,
    IdPrioritasTicket uniqueidentifier NULL,
    IdStatusTicket uniqueidentifier NULL,
    JudulTicket varchar(255) NOT NULL,
    DeskripsiMasalah nvarchar(max) NULL,
    DibuatDariPesanId uniqueidentifier NULL,
    DitugaskanKepada uniqueidentifier NULL,
    TglDitugaskan datetime2 NULL,
    TglTargetSelesai datetime2 NULL,
    TglSelesai datetime2 NULL,
    TglDitutup datetime2 NULL,
    DitutupOleh uniqueidentifier NULL,
    RingkasanAi nvarchar(max) NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TTicket_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TTicket PRIMARY KEY (Id),
    CONSTRAINT UQ_TTicket_NomorTicket UNIQUE (NomorTicket),
    CONSTRAINT FK_TTicket_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id),
    CONSTRAINT FK_TTicket_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
    CONSTRAINT FK_TTicket_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
    CONSTRAINT FK_TTicket_MKategoriTicket FOREIGN KEY (IdKategoriTicket) REFERENCES MKategoriTicket(Id),
    CONSTRAINT FK_TTicket_MPrioritasTicket FOREIGN KEY (IdPrioritasTicket) REFERENCES MPrioritasTicket(Id),
    CONSTRAINT FK_TTicket_MStatusTicket FOREIGN KEY (IdStatusTicket) REFERENCES MStatusTicket(Id),
    CONSTRAINT FK_TTicket_TChatD FOREIGN KEY (DibuatDariPesanId) REFERENCES TChatD(Id)
);
GO

CREATE TABLE TTicketD (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TTicketD_Id DEFAULT NEWSEQUENTIALID(),
    IdTicket uniqueidentifier NOT NULL,
    JenisAktivitas varchar(100) NOT NULL,
    IsiAktivitas nvarchar(max) NULL,
    StatusSebelum varchar(100) NULL,
    StatusSesudah varchar(100) NULL,
    DitujukanKepada uniqueidentifier NULL,
    TglAktivitas datetime2 NOT NULL CONSTRAINT DF_TTicketD_TglAktivitas DEFAULT SYSDATETIME(),
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TTicketD_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TTicketD PRIMARY KEY (Id),
    CONSTRAINT FK_TTicketD_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id)
);
GO

CREATE TABLE TTicketDPenugasan (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TTicketDPenugasan_Id DEFAULT NEWSEQUENTIALID(),
    IdTicket uniqueidentifier NOT NULL,
    DitugaskanDari uniqueidentifier NULL,
    DitugaskanKepada uniqueidentifier NOT NULL,
    AlasanPenugasan varchar(500) NULL,
    TglPenugasan datetime2 NOT NULL CONSTRAINT DF_TTicketDPenugasan_TglPenugasan DEFAULT SYSDATETIME(),
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TTicketDPenugasan_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TTicketDPenugasan PRIMARY KEY (Id),
    CONSTRAINT FK_TTicketDPenugasan_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id)
);
GO

CREATE TABLE TTicketDLampiran (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TTicketDLampiran_Id DEFAULT NEWSEQUENTIALID(),
    IdTicket uniqueidentifier NOT NULL,
    NamaFile varchar(255) NOT NULL,
    PathFile varchar(1000) NOT NULL,
    TipeFile varchar(100) NULL,
    UkuranFile bigint NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TTicketDLampiran_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TTicketDLampiran PRIMARY KEY (Id),
    CONSTRAINT FK_TTicketDLampiran_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id)
);
GO

CREATE TABLE TAiPermintaan (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TAiPermintaan_Id DEFAULT NEWSEQUENTIALID(),
    IdAiProvider uniqueidentifier NULL,
    JenisPermintaan varchar(100) NOT NULL,
    ProviderAi varchar(50) NOT NULL,
    ModelAi varchar(100) NULL,
    IdChat uniqueidentifier NULL,
    IdTicket uniqueidentifier NULL,
    PromptRingkas nvarchar(max) NULL,
    PromptJson nvarchar(max) NULL,
    StatusPermintaan varchar(50) NOT NULL CONSTRAINT DF_TAiPermintaan_StatusPermintaan DEFAULT 'Baru',
    TglMulai datetime2 NULL,
    TglSelesai datetime2 NULL,
    PesanError nvarchar(max) NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TAiPermintaan_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TAiPermintaan PRIMARY KEY (Id),
    CONSTRAINT FK_TAiPermintaan_MAiProvider FOREIGN KEY (IdAiProvider) REFERENCES MAiProvider(Id),
    CONSTRAINT FK_TAiPermintaan_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id),
    CONSTRAINT FK_TAiPermintaan_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id)
);
GO

CREATE TABLE TAiRespon (
    Id uniqueidentifier NOT NULL CONSTRAINT DF_TAiRespon_Id DEFAULT NEWSEQUENTIALID(),
    IdAiPermintaan uniqueidentifier NOT NULL,
    JenisRespon varchar(100) NOT NULL,
    ResponRingkas nvarchar(max) NULL,
    ResponJson nvarchar(max) NULL,
    TokenInput int NULL,
    TokenOutput int NULL,
    BiayaEstimasi decimal(18, 6) NULL,
    DisetujuiOleh uniqueidentifier NULL,
    TglDisetujui datetime2 NULL,
    TglBuat datetime2 NOT NULL CONSTRAINT DF_TAiRespon_TglBuat DEFAULT SYSDATETIME(),
    DibuatOleh uniqueidentifier NULL,
    TglEdit datetime2 NULL,
    DieditOleh uniqueidentifier NULL,
    CONSTRAINT PK_TAiRespon PRIMARY KEY (Id),
    CONSTRAINT FK_TAiRespon_TAiPermintaan FOREIGN KEY (IdAiPermintaan) REFERENCES TAiPermintaan(Id)
);
GO

ALTER TABLE TChatD
ADD CONSTRAINT FK_TChatD_TAiRespon FOREIGN KEY (IdAiRespon) REFERENCES TAiRespon(Id);
GO

CREATE INDEX IX_MCustomer_NamaCustomer ON MCustomer (NamaCustomer);
CREATE INDEX IX_MInstansi_NamaInstansi ON MInstansi (NamaInstansi);
CREATE INDEX IX_MNomorWhatsapp_NomorWhatsapp ON MNomorWhatsapp (NomorWhatsapp);
CREATE INDEX IX_MGrupWhatsapp_IdInstansi ON MGrupWhatsapp (IdInstansi);
CREATE INDEX IX_MGrupWhatsapp_IdGrupWaha ON MGrupWhatsapp (IdGrupWaha);
CREATE INDEX IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp ON MAnggotaGrupWhatsapp (IdGrupWhatsapp);
CREATE INDEX IX_MProdukCustomer_IdCustomer ON MProdukCustomer (IdCustomer);
CREATE INDEX IX_MHariLibur_TanggalLibur ON MHariLibur (TanggalLibur, NonAktif);
CREATE INDEX IX_TLogAktivitas_TglAktivitas ON TLogAktivitas (TglAktivitas);
CREATE INDEX IX_TLogError_TglError ON TLogError (TglError);
CREATE INDEX IX_TLogIntegrasi_TglRequest ON TLogIntegrasi (TglRequest);
CREATE INDEX IX_TLogWebhookWaha_TglDiterima ON TLogWebhookWaha (TglDiterima);
CREATE INDEX IX_TLogWebhookWaha_SudahDiproses ON TLogWebhookWaha (SudahDiproses);
CREATE INDEX IX_TChat_NomorWhatsapp ON TChat (NomorWhatsapp);
CREATE INDEX IX_TChat_IdWahaTerdeteksi ON TChat (IdWahaTerdeteksi);
CREATE INDEX IX_TChat_NomorWhatsappTerdeteksi ON TChat (NomorWhatsappTerdeteksi);
CREATE INDEX IX_TChat_IdCustomer ON TChat (IdCustomer);
CREATE INDEX IX_TChat_IdInstansi ON TChat (IdInstansi);
CREATE INDEX IX_TChat_IdGrupWhatsapp ON TChat (IdGrupWhatsapp);
CREATE INDEX IX_TChat_IdStatusChat ON TChat (IdStatusChat);
CREATE INDEX IX_TChat_DitugaskanKepada ON TChat (DitugaskanKepada);
CREATE INDEX IX_TChat_TglChatTerakhir ON TChat (TglChatTerakhir);
CREATE INDEX IX_TChatD_IdChat_TglPesan ON TChatD (IdChat, TglPesan);
CREATE INDEX IX_TChatD_IdPesanWaha ON TChatD (IdPesanWaha);
CREATE INDEX IX_TTicket_IdCustomer ON TTicket (IdCustomer);
CREATE INDEX IX_TTicket_IdInstansi ON TTicket (IdInstansi);
CREATE INDEX IX_TTicket_IdStatusTicket ON TTicket (IdStatusTicket);
CREATE INDEX IX_TTicket_DitugaskanKepada ON TTicket (DitugaskanKepada);
CREATE INDEX IX_TTicket_TglTargetSelesai ON TTicket (TglTargetSelesai);
CREATE INDEX IX_TTicketD_IdTicket_TglAktivitas ON TTicketD (IdTicket, TglAktivitas);
CREATE INDEX IX_TAiPermintaan_IdChat ON TAiPermintaan (IdChat);
CREATE INDEX IX_TAiPermintaan_IdTicket ON TAiPermintaan (IdTicket);
CREATE INDEX IX_TChatD_IdAiRespon ON TChatD (IdAiRespon);
GO

INSERT INTO MPeran (KodePeran, NamaPeran, Keterangan)
VALUES
('ADMIN', 'Admin', 'Akses penuh aplikasi'),
('SUPERVISOR_CS', 'Supervisor CS', 'Monitoring dan pengaturan customer service'),
('CS', 'Customer Service', 'Menangani chat dan membuat ticket'),
('DEVELOPER', 'Developer', 'Menangani ticket teknis'),
('VIEWER', 'Viewer', 'Melihat dashboard dan laporan');
GO

INSERT INTO MStatusChat (KodeStatusChat, NamaStatusChat, Urutan, Warna)
VALUES
('BARU', 'Baru', 10, 'info'),
('MENUNGGU_CS', 'Menunggu CS', 20, 'warning'),
('DALAM_PROSES', 'Dalam Proses', 30, 'primary'),
('MENUNGGU_CUSTOMER', 'Menunggu Customer', 40, 'gray'),
('SELESAI', 'Selesai', 50, 'success'),
('DITUTUP', 'Ditutup', 60, 'gray');
GO

INSERT INTO MStatusTicket (KodeStatusTicket, NamaStatusTicket, Urutan, StatusFinal, Warna)
VALUES
('DRAFT', 'Draft', 10, 0, 'gray'),
('BARU', 'Baru', 20, 0, 'info'),
('DIANALISA_CS', 'Dianalisa CS', 30, 0, 'warning'),
('BUTUH_DATA_CUSTOMER', 'Butuh Data Customer', 40, 0, 'warning'),
('DITERUSKAN_DEVELOPER', 'Diteruskan ke Developer', 50, 0, 'primary'),
('DALAM_PENGERJAAN', 'Dalam Pengerjaan', 60, 0, 'primary'),
('MENUNGGU_DEPLOY', 'Menunggu Deploy', 70, 0, 'warning'),
('SELESAI', 'Selesai', 80, 1, 'success'),
('DITUTUP', 'Ditutup', 90, 1, 'gray'),
('DIBATALKAN', 'Dibatalkan', 100, 1, 'danger');
GO

INSERT INTO MPrioritasTicket (KodePrioritas, NamaPrioritas, Urutan, BatasSlaMenit, Warna)
VALUES
('RENDAH', 'Rendah', 10, 4320, 'gray'),
('NORMAL', 'Normal', 20, 1440, 'info'),
('TINGGI', 'Tinggi', 30, 480, 'warning'),
('KRITIS', 'Kritis', 40, 120, 'danger');
GO

INSERT INTO MKategoriTicket (KodeKategori, NamaKategori, Keterangan)
VALUES
('BUG', 'Bug Aplikasi', 'Masalah error atau bug aplikasi'),
('DATA', 'Masalah Data', 'Masalah data master atau transaksi'),
('AKSES', 'Masalah Akses', 'Login, role, permission, atau akses menu'),
('REQUEST', 'Permintaan Fitur', 'Permintaan fitur baru atau perubahan fitur'),
('KONSULTASI', 'Konsultasi', 'Pertanyaan penggunaan aplikasi');
GO

INSERT INTO MPengaturanAi (
    KodePengaturan,
    NamaPengaturan,
    AutoReplyAktif,
    AutoReplyDiluarJamKerja,
    AutoReplyHariLibur,
    AutoReplyJamKerjaSapaan,
    AutoReplyJamKerjaBerlanjut,
    JamKerjaMulai,
    JamKerjaSelesai,
    HariKerja,
    ZonaWaktu,
    ProviderAi,
    ModelAi,
    BaseUrl,
    PromptSistem,
    TemplateDiluarJamKerja,
    TemplateHariLibur,
    TemplateJamKerjaSapaan,
    TemplateFallback,
    NotifikasiChatBelumTerbalasAktif,
    MenitTungguNotifikasi,
    JedaNotifikasiMenit,
    KodePeranPenerimaNotifikasi,
    TemplateNotifikasiChatBelumTerbalas,
    BatasRiwayatPesan,
    KirimKeWaha,
    ModeKirim
)
VALUES (
    'DEFAULT',
    'Pengaturan Default AI Agent',
    0,
    1,
    1,
    1,
    0,
    '08:00',
    '17:00',
    '1,2,3,4,5',
    'Asia/Jakarta',
    'OpenAI',
    'gpt-5',
    'https://api.openai.com/v1/responses',
    N'Anda adalah AI Agent customer service VPoint Care. Jawab dalam Bahasa Indonesia yang sopan, singkat, jelas, dan jangan membuat janji teknis yang belum dipastikan. Jika masalah perlu ditangani manusia, arahkan bahwa tim customer service akan menindaklanjuti.',
    N'Terima kasih sudah menghubungi VPoint Care. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.',
    N'Terima kasih sudah menghubungi VPoint Care. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.',
    N'Halo, terima kasih sudah menghubungi VPoint Care. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.',
    N'Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.',
    1,
    10,
    30,
    'ADMIN,SUPERVISOR_CS,CS',
    N'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}',
    8,
    0,
    'DraftLokal'
);
GO

/* Added by scalability-optimization-and-chatbot */
IF OBJECT_ID(N'TChatbotInternal', 'U') IS NULL
BEGIN
    CREATE TABLE TChatbotInternal (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_TChatbotInternal_Id DEFAULT NEWSEQUENTIALID(),
        IdPengguna uniqueidentifier NOT NULL,
        PeranPengirim varchar(20) NOT NULL,
        IsiPesan nvarchar(max) NOT NULL,
        IdAiRespon uniqueidentifier NULL,
        KonteksJson nvarchar(max) NULL,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_TChatbotInternal_TglBuat DEFAULT SYSDATETIME(),
        CONSTRAINT PK_TChatbotInternal PRIMARY KEY (Id),
        CONSTRAINT FK_TChatbotInternal_MPengguna FOREIGN KEY (IdPengguna) REFERENCES MPengguna(Id),
        CONSTRAINT CK_TChatbotInternal_Peran CHECK (PeranPengirim IN ('user', 'assistant'))
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatbotInternal_Pengguna_Tgl' AND object_id = OBJECT_ID('TChatbotInternal'))
    CREATE INDEX IX_TChatbotInternal_Pengguna_Tgl ON TChatbotInternal (IdPengguna, TglBuat DESC);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdPesanWaha_Partial' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdPesanWaha_Partial ON TChatD (IdPesanWaha) WHERE IdPesanWaha IS NOT NULL;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_Arah_Dikirim_Tgl' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_Arah_Dikirim_Tgl ON TChatD (ArahPesan, DikirimOlehCustomer, TglPesan DESC) INCLUDE (IdChat, IsiPesan);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_IdChat_Arah_Ai_Tgl' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_IdChat_Arah_Ai_Tgl ON TChatD (IdChat, ArahPesan, DihasilkanOlehAi, TglPesan DESC);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatD_TglPesan_Arah_Status' AND object_id = OBJECT_ID('TChatD'))
    CREATE INDEX IX_TChatD_TglPesan_Arah_Status ON TChatD (TglPesan) INCLUDE (IdChat, ArahPesan, DihasilkanOlehAi, StatusKirim);
GO
