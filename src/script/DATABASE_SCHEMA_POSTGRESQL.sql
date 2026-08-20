/*
    Aplikasi: CareDesk (Care Desk System)
    Target: PostgreSQL 14+ / 16+
    PK: UUID DEFAULT gen_random_uuid()
    Catatan:
    - Master data diawali M dan memiliki NonAktif.
    - Transaksi data diawali T.
    - Semua tabel memiliki TglBuat, DibuatOleh, TglEdit, DieditOleh.
    - Audit user tidak dibuat FK agar data historis tetap aman saat user berubah.
*/

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE IF NOT EXISTS "MPeran" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodePeran" varchar(50) NOT NULL,
    "NamaPeran" varchar(100) NOT NULL,
    "Keterangan" varchar(255) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPeran" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MPeran_KodePeran" UNIQUE ("KodePeran")
);

CREATE TABLE IF NOT EXISTS "MHakAkses" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdHakAkses" UUID NULL,
    "KodeHakAkses" varchar(100) NULL,
    "NamaHakAkses" varchar(150) NOT NULL,
    "NamaHakAksesId" varchar(150) NULL,
    "NamaHakAksesEn" varchar(150) NULL,
    "Modul" varchar(100) NOT NULL,
    "ModulId" varchar(100) NULL,
    "ModulEn" varchar(100) NULL,
    "Keterangan" varchar(255) NULL,
    "KeteranganId" varchar(255) NULL,
    "KeteranganEn" varchar(255) NULL,
    "SortOrder" int NULL,
    "IconString" varchar(100) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MHakAkses" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MHakAkses_IdHakAkses" FOREIGN KEY ("IdHakAkses") REFERENCES "MHakAkses" ("Id")
);

CREATE UNIQUE INDEX IF NOT EXISTS  "UX_MHakAkses_KodeHakAkses_NotNull" ON "MHakAkses" ("KodeHakAkses") WHERE "KodeHakAkses" IS NOT NULL;

CREATE TABLE IF NOT EXISTS "MPeranHakAkses" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdPeran" UUID NOT NULL,
    "IdHakAkses" UUID NOT NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPeranHakAkses" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MPeranHakAkses_MPeran" FOREIGN KEY ("IdPeran") REFERENCES "MPeran" ("Id"),
    CONSTRAINT "FK_MPeranHakAkses_MHakAkses" FOREIGN KEY ("IdHakAkses") REFERENCES "MHakAkses" ("Id"),
    CONSTRAINT "UQ_MPeranHakAkses" UNIQUE ("IdPeran", "IdHakAkses")
);

CREATE TABLE IF NOT EXISTS "MPengguna" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdPeran" UUID NOT NULL,
    "NamaPengguna" varchar(150) NOT NULL,
    "Email" varchar(150) NOT NULL,
    "Password" varchar(255) NOT NULL,
    "NomorWhatsappInternal" varchar(30) NULL,
    "FotoProfilPath" VARCHAR(500) NULL,
    "Jabatan" varchar(100) NULL,
    "RememberToken" varchar(100) NULL,
    "EmailTerverifikasiPada" TIMESTAMP NULL,
    "LoginTerakhirPada" TIMESTAMP NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPengguna" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MPengguna_MPeran" FOREIGN KEY ("IdPeran") REFERENCES "MPeran" ("Id"),
    CONSTRAINT "UQ_MPengguna_Email" UNIQUE ("Email")
);

CREATE TABLE IF NOT EXISTS "MInstansi" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeInstansi" varchar(50) NOT NULL,
    "NamaInstansi" varchar(200) NOT NULL,
    "Alamat" varchar(500) NULL,
    "Kota" varchar(100) NULL,
    "Provinsi" varchar(100) NULL,
    "Negara" varchar(100) NULL,
    "KodePos" varchar(20) NULL,
    "Telepon" varchar(50) NULL,
    "Email" varchar(150) NULL,
    "Website" varchar(200) NULL,
    "SumberData" varchar(50) NULL,
    "IdExternal" varchar(100) NULL,
    "TglSinkronTerakhir" TIMESTAMP NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MInstansi" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MInstansi_KodeInstansi" UNIQUE ("KodeInstansi")
);

CREATE TABLE IF NOT EXISTS "MCustomer" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdInstansi" UUID NULL,
    "KodeCustomer" varchar(50) NOT NULL,
    "NamaCustomer" varchar(200) NOT NULL,
    "Email" varchar(150) NULL,
    "Telepon" varchar(50) NULL,
    "Jabatan" varchar(100) NULL,
    "Catatan" varchar(1000) NULL,
    "SumberData" varchar(50) NULL,
    "IdExternal" varchar(100) NULL,
    "TglSinkronTerakhir" TIMESTAMP NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MCustomer" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MCustomer_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id"),
    CONSTRAINT "UQ_MCustomer_KodeCustomer" UNIQUE ("KodeCustomer")
);

CREATE TABLE IF NOT EXISTS "MNomorWhatsapp" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdCustomer" UUID NULL,
    "IdInstansi" UUID NULL,
    "NomorWhatsapp" varchar(30) NOT NULL,
    "NamaKontak" varchar(150) NULL,
    "JabatanKontak" varchar(100) NULL,
    "NomorUtama" BOOLEAN NOT NULL DEFAULT FALSE,
    "Terverifikasi" BOOLEAN NOT NULL DEFAULT FALSE,
    "SumberData" varchar(50) NULL,
    "IdExternal" varchar(100) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MNomorWhatsapp" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MNomorWhatsapp_MCustomer" FOREIGN KEY ("IdCustomer") REFERENCES "MCustomer" ("Id"),
    CONSTRAINT "FK_MNomorWhatsapp_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id"),
    CONSTRAINT "UQ_MNomorWhatsapp_NomorWhatsapp" UNIQUE ("NomorWhatsapp")
);

CREATE TABLE IF NOT EXISTS "MGrupWhatsapp" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdInstansi" UUID NOT NULL,
    "KodeGrup" varchar(50) NOT NULL,
    "NamaGrup" varchar(200) NOT NULL,
    "IdGrupWaha" varchar(200) NULL,
    "NomorGrupWhatsapp" varchar(100) NULL,
    "Deskripsi" varchar(500) NULL,
    "SumberData" varchar(50) NULL,
    "IdExternal" varchar(100) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MGrupWhatsapp" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MGrupWhatsapp_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id"),
    CONSTRAINT "UQ_MGrupWhatsapp_KodeGrup" UNIQUE ("KodeGrup")
);

CREATE TABLE IF NOT EXISTS "MAnggotaGrupWhatsapp" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdGrupWhatsapp" UUID NOT NULL,
    "IdNomorWhatsapp" UUID NOT NULL,
    "IdCustomer" UUID NULL,
    "PeranAnggota" varchar(100) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MAnggotaGrupWhatsapp" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MAnggotaGrupWhatsapp_MGrupWhatsapp" FOREIGN KEY ("IdGrupWhatsapp") REFERENCES "MGrupWhatsapp" ("Id"),
    CONSTRAINT "FK_MAnggotaGrupWhatsapp_MNomorWhatsapp" FOREIGN KEY ("IdNomorWhatsapp") REFERENCES "MNomorWhatsapp" ("Id"),
    CONSTRAINT "FK_MAnggotaGrupWhatsapp_MCustomer" FOREIGN KEY ("IdCustomer") REFERENCES "MCustomer" ("Id"),
    CONSTRAINT "UQ_MAnggotaGrupWhatsapp" UNIQUE ("IdGrupWhatsapp", "IdNomorWhatsapp")
);

CREATE TABLE IF NOT EXISTS "MProdukCustomer" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdCustomer" UUID NULL,
    "IdInstansi" UUID NULL,
    "KodeProduk" varchar(50) NOT NULL,
    "NamaProduk" varchar(150) NOT NULL,
    "Keterangan" varchar(500) NULL,
    "TglMulai" TIMESTAMP NULL,
    "TglBerakhir" TIMESTAMP NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MProdukCustomer" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_MProdukCustomer_MCustomer" FOREIGN KEY ("IdCustomer") REFERENCES "MCustomer" ("Id"),
    CONSTRAINT "FK_MProdukCustomer_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id")
);

CREATE TABLE IF NOT EXISTS "MStatusChat" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeStatusChat" varchar(50) NOT NULL,
    "NamaStatusChat" varchar(100) NOT NULL,
    "Urutan" int NOT NULL DEFAULT 0,
    "Warna" varchar(30) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MStatusChat" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MStatusChat_KodeStatusChat" UNIQUE ("KodeStatusChat")
);

CREATE TABLE IF NOT EXISTS "MKategoriTicket" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeKategori" varchar(50) NOT NULL,
    "NamaKategori" varchar(150) NOT NULL,
    "Keterangan" varchar(500) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MKategoriTicket" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MKategoriTicket_KodeKategori" UNIQUE ("KodeKategori")
);

CREATE TABLE IF NOT EXISTS "MPrioritasTicket" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodePrioritas" varchar(50) NOT NULL,
    "NamaPrioritas" varchar(100) NOT NULL,
    "Urutan" int NOT NULL DEFAULT 0,
    "BatasSlaMenit" int NULL,
    "Warna" varchar(30) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPrioritasTicket" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MPrioritasTicket_KodePrioritas" UNIQUE ("KodePrioritas")
);

CREATE TABLE IF NOT EXISTS "MStatusTicket" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeStatusTicket" varchar(50) NOT NULL,
    "NamaStatusTicket" varchar(100) NOT NULL,
    "Urutan" int NOT NULL DEFAULT 0,
    "StatusFinal" BOOLEAN NOT NULL DEFAULT FALSE,
    "Warna" varchar(30) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MStatusTicket" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MStatusTicket_KodeStatusTicket" UNIQUE ("KodeStatusTicket")
);

CREATE TABLE IF NOT EXISTS "MSesiWhatsapp" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeSesi" varchar(50) NOT NULL,
    "NamaSesi" varchar(150) NOT NULL,
    "BaseUrlWaha" varchar(255) NOT NULL,
    "ApiKey" varchar(255) NULL,
    "NomorTerhubung" varchar(30) NULL,
    "StatusSesi" varchar(50) NOT NULL DEFAULT 'TidakAktif',
    "WebhookToken" varchar(255) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MSesiWhatsapp" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MSesiWhatsapp_KodeSesi" UNIQUE ("KodeSesi")
);

CREATE TABLE IF NOT EXISTS "MEndpointIntegrasi" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeEndpoint" varchar(100) NOT NULL,
    "NamaEndpoint" varchar(150) NOT NULL,
    "UrlEndpoint" varchar(500) NOT NULL,
    "MetodeHttp" varchar(10) NOT NULL,
    "HeaderJson" TEXT NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MEndpointIntegrasi" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MEndpointIntegrasi_KodeEndpoint" UNIQUE ("KodeEndpoint")
);

CREATE TABLE IF NOT EXISTS "MAiProvider" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodeProvider" varchar(50) NOT NULL,
    "NamaProvider" varchar(100) NOT NULL,
    "BaseUrl" varchar(255) NULL,
    "ApiKeyTerenkripsi" varchar(1000) NULL,
    "ModelDefault" varchar(100) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MAiProvider" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MAiProvider_KodeProvider" UNIQUE ("KodeProvider")
);

CREATE TABLE IF NOT EXISTS "MHariLibur" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "TanggalLibur" date NOT NULL,
    "NamaHariLibur" varchar(200) NOT NULL,
    "Keterangan" varchar(1000) NULL,
    "BerlakuTahunan" BOOLEAN NOT NULL DEFAULT FALSE,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MHariLibur" PRIMARY KEY ("Id")
);

CREATE TABLE IF NOT EXISTS "MPengaturanAi" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodePengaturan" varchar(50) NOT NULL,
    "NamaPengaturan" varchar(100) NOT NULL,
    "AutoReplyAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "AutoReplyDiluarJamKerja" BOOLEAN NOT NULL DEFAULT TRUE,
    "AutoReplyHariLibur" BOOLEAN NOT NULL DEFAULT TRUE,
    "AutoReplyJamKerjaSapaan" BOOLEAN NOT NULL DEFAULT TRUE,
    "AutoReplyJamKerjaBerlanjut" BOOLEAN NOT NULL DEFAULT FALSE,
    "JamKerjaMulai" time(0) NOT NULL DEFAULT '08:00',
    "JamKerjaSelesai" time(0) NOT NULL DEFAULT '17:00',
    "HariKerja" varchar(50) NOT NULL DEFAULT '1,2,3,4,5',
    "ZonaWaktu" varchar(100) NOT NULL DEFAULT 'Asia/Jakarta',
    "ProviderAi" varchar(50) NOT NULL DEFAULT 'OpenAI',
    "ModelAi" varchar(100) NULL,
    "BaseUrl" varchar(255) NULL,
    "ApiKeyTerenkripsi" TEXT NULL,
    "PromptSistem" TEXT NULL,
    "TemplateDiluarJamKerja" TEXT NULL,
    "TemplateHariLibur" TEXT NULL,
    "TemplateJamKerjaSapaan" TEXT NULL,
    "TemplateFallback" TEXT NULL,
    "NotifikasiChatBelumTerbalasAktif" BOOLEAN NOT NULL DEFAULT TRUE,
    "MenitTungguNotifikasi" int NOT NULL DEFAULT 10,
    "JedaNotifikasiMenit" int NOT NULL DEFAULT 30,
    "KodePeranPenerimaNotifikasi" varchar(200) NOT NULL DEFAULT 'ADMIN,SUPERVISOR_CS,CS',
    "TemplateNotifikasiChatBelumTerbalas" TEXT NULL,
    "BatasRiwayatPesan" int NOT NULL DEFAULT 8,
    "KirimKeWaha" BOOLEAN NOT NULL DEFAULT FALSE,
    "ModeKirim" varchar(50) NOT NULL DEFAULT 'DraftLokal',
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPengaturanAi" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MPengaturanAi_KodePengaturan" UNIQUE ("KodePengaturan")
);

CREATE TABLE IF NOT EXISTS "MPengaturanAplikasi" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodePengaturan" varchar(50) NOT NULL DEFAULT 'DEFAULT',
    "NamaAplikasi" varchar(100) NULL,
    "Tagline" varchar(255) NULL,
    "NamaPerusahaan" varchar(200) NULL,
    "LogoUtamaPath" varchar(500) NULL,
    "LogoSekunderPath" varchar(500) NULL,
    "FaviconPath" varchar(500) NULL,
    "TeksFooter" TEXT NULL,
    "BahasaDefault" varchar(10) NOT NULL DEFAULT 'id',
    "ZonaWaktu" varchar(100) NOT NULL DEFAULT 'Asia/Jakarta',
    "FormatTanggal" varchar(50) NOT NULL DEFAULT 'd/m/Y',
    "EmailSupport" varchar(150) NULL,
    "NomorTeleponSupport" varchar(50) NULL,
    "AlamatKantor" varchar(500) NULL,
    "MailMailer" varchar(50) NOT NULL DEFAULT 'smtp',
    "MailHost" varchar(255) NULL,
    "MailPort" int NOT NULL DEFAULT 587,
    "MailUsername" varchar(255) NULL,
    "MailPasswordTerenkripsi" TEXT NULL,
    "MailEncryption" varchar(20) NOT NULL DEFAULT 'tls',
    "MailFromAddress" varchar(255) NULL,
    "MailFromName" varchar(255) NULL,
    "SetupSelesai" BOOLEAN NOT NULL DEFAULT FALSE,
    "LangkahOnboardingJson" TEXT NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPengaturanAplikasi" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MPengaturanAplikasi_KodePengaturan" UNIQUE ("KodePengaturan")
);

CREATE TABLE IF NOT EXISTS "MPengetahuan" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "KodePengetahuan" varchar(50) NOT NULL,
    "JudulPengetahuan" varchar(200) NOT NULL,
    "IsiPengetahuan" TEXT NOT NULL,
    "Tag" varchar(500) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_MPengetahuan" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_MPengetahuan_KodePengetahuan" UNIQUE ("KodePengetahuan")
);

CREATE TABLE IF NOT EXISTS "TLogAktivitas" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdPengguna" UUID NULL,
    "Modul" varchar(100) NOT NULL,
    "Aksi" varchar(100) NOT NULL,
    "Keterangan" varchar(1000) NULL,
    "IpAddress" varchar(50) NULL,
    "UserAgent" varchar(500) NULL,
    "DataSebelumJson" TEXT NULL,
    "DataSesudahJson" TEXT NULL,
    "TglAktivitas" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TLogAktivitas" PRIMARY KEY ("Id")
);

CREATE TABLE IF NOT EXISTS "TLogError" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "LevelError" varchar(50) NOT NULL,
    "PesanError" TEXT NOT NULL,
    "FileError" varchar(500) NULL,
    "BarisError" int NULL,
    "StackTrace" TEXT NULL,
    "ContextJson" TEXT NULL,
    "TglError" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TLogError" PRIMARY KEY ("Id")
);

CREATE TABLE IF NOT EXISTS "TLogIntegrasi" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdEndpointIntegrasi" UUID NULL,
    "KodeIntegrasi" varchar(100) NOT NULL,
    "UrlEndpoint" varchar(500) NOT NULL,
    "MetodeHttp" varchar(10) NOT NULL,
    "RequestJson" TEXT NULL,
    "ResponseJson" TEXT NULL,
    "StatusHttp" int NULL,
    "Berhasil" BOOLEAN NOT NULL DEFAULT FALSE,
    "PesanError" TEXT NULL,
    "TglRequest" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglResponse" TIMESTAMP NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TLogIntegrasi" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TLogIntegrasi_MEndpointIntegrasi" FOREIGN KEY ("IdEndpointIntegrasi") REFERENCES "MEndpointIntegrasi" ("Id")
);

CREATE TABLE IF NOT EXISTS "TLogWebhookWaha" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdSesiWhatsapp" UUID NULL,
    "JenisEvent" varchar(100) NOT NULL,
    "PayloadJson" TEXT NOT NULL,
    "TglDiterima" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "SudahDiproses" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglDiproses" TIMESTAMP NULL,
    "PesanError" TEXT NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TLogWebhookWaha" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TLogWebhookWaha_MSesiWhatsapp" FOREIGN KEY ("IdSesiWhatsapp") REFERENCES "MSesiWhatsapp" ("Id")
);

CREATE TABLE IF NOT EXISTS "TChat" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdSesiWhatsapp" UUID NOT NULL,
    "IdStatusChat" UUID NULL,
    "IdCustomer" UUID NULL,
    "IdInstansi" UUID NULL,
    "IdNomorWhatsapp" UUID NULL,
    "IdGrupWhatsapp" UUID NULL,
    "JenisChat" varchar(30) NOT NULL DEFAULT 'Pribadi',
    "NomorWhatsapp" varchar(30) NOT NULL,
    "NamaKontak" varchar(150) NULL,
    "NamaGrupWhatsapp" varchar(200) NULL,
    "GroupName" VARCHAR(200) NULL,
    "IdWahaTerdeteksi" varchar(200) NULL,
    "NomorWhatsappTerdeteksi" varchar(30) NULL,
    "UrlFotoProfil" VARCHAR(1000) NULL,
    "TglFotoProfilDiambil" TIMESTAMP NULL,
    "Prioritas" varchar(50) NOT NULL DEFAULT 'Normal',
    "DitugaskanKepada" UUID NULL,
    "DiambilOleh" UUID NULL,
    "TglDiambil" TIMESTAMP NULL,
    "TglChatTerakhir" TIMESTAMP NULL,
    "TglDibalasTerakhir" TIMESTAMP NULL,
    "JumlahPesanBelumDibaca" int NOT NULL DEFAULT 0,
    "DitutupOleh" UUID NULL,
    "TglDitutup" TIMESTAMP NULL,
    "RingkasanAi" TEXT NULL,
    "AutoReplyAiAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "AiSudahMenyapa" BOOLEAN NOT NULL DEFAULT FALSE,
    "ModeAutoReplyAi" varchar(50) NOT NULL DEFAULT 'Default',
    "TglAutoReplyAiTerakhir" TIMESTAMP NULL,
    "TglNotifikasiBelumTerbalasTerakhir" TIMESTAMP NULL,
    "JumlahNotifikasiBelumTerbalas" int NOT NULL DEFAULT 0,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TChat" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TChat_MSesiWhatsapp" FOREIGN KEY ("IdSesiWhatsapp") REFERENCES "MSesiWhatsapp" ("Id"),
    CONSTRAINT "FK_TChat_MStatusChat" FOREIGN KEY ("IdStatusChat") REFERENCES "MStatusChat" ("Id"),
    CONSTRAINT "FK_TChat_MCustomer" FOREIGN KEY ("IdCustomer") REFERENCES "MCustomer" ("Id"),
    CONSTRAINT "FK_TChat_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id"),
    CONSTRAINT "FK_TChat_MNomorWhatsapp" FOREIGN KEY ("IdNomorWhatsapp") REFERENCES "MNomorWhatsapp" ("Id"),
    CONSTRAINT "FK_TChat_MGrupWhatsapp" FOREIGN KEY ("IdGrupWhatsapp") REFERENCES "MGrupWhatsapp" ("Id")
);

CREATE TABLE IF NOT EXISTS "TChatD" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdChat" UUID NOT NULL,
    "IdLogWebhookWaha" UUID NULL,
    "IdPesanWaha" varchar(200) NULL,
    "ArahPesan" varchar(20) NOT NULL,
    "JenisPesan" varchar(50) NOT NULL DEFAULT 'Teks',
    "IsiPesan" TEXT NULL,
    "UrlMedia" varchar(1000) NULL,
    "NamaFileMedia" varchar(255) NULL,
    "TipeMime" varchar(100) NULL,
    "PayloadJson" TEXT NULL,
    "PengirimNomorWhatsapp" varchar(30) NULL,
    "PengirimNamaKontak" varchar(150) NULL,
    "DikirimOlehCustomer" BOOLEAN NOT NULL DEFAULT FALSE,
    "DihasilkanOlehAi" BOOLEAN NOT NULL DEFAULT FALSE,
    "IdAiRespon" UUID NULL,
    "DibalasOleh" UUID NULL,
    "TglPesan" TIMESTAMP NOT NULL,
    "TglDikirim" TIMESTAMP NULL,
    "TglDibaca" TIMESTAMP NULL,
    "StatusKirim" varchar(50) NULL,
    "PesanError" TEXT NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TChatD" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TChatD_TChat" FOREIGN KEY ("IdChat") REFERENCES "TChat" ("Id"),
    CONSTRAINT "FK_TChatD_TLogWebhookWaha" FOREIGN KEY ("IdLogWebhookWaha") REFERENCES "TLogWebhookWaha" ("Id")
);

CREATE TABLE IF NOT EXISTS "TChatDPenugasan" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdChat" UUID NOT NULL,
    "DitugaskanDari" UUID NULL,
    "DitugaskanKepada" UUID NOT NULL,
    "AlasanPenugasan" varchar(500) NULL,
    "TglPenugasan" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TChatDPenugasan" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TChatDPenugasan_TChat" FOREIGN KEY ("IdChat") REFERENCES "TChat" ("Id")
);

CREATE TABLE IF NOT EXISTS "TChatDCatatanInternal" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdChat" UUID NOT NULL,
    "IsiCatatan" TEXT NOT NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TChatDCatatanInternal" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TChatDCatatanInternal_TChat" FOREIGN KEY ("IdChat") REFERENCES "TChat" ("Id")
);

CREATE TABLE IF NOT EXISTS "TTicket" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "NomorTicket" varchar(50) NOT NULL,
    "IdChat" UUID NULL,
    "IdCustomer" UUID NULL,
    "IdInstansi" UUID NULL,
    "IdKategoriTicket" UUID NULL,
    "IdPrioritasTicket" UUID NULL,
    "IdStatusTicket" UUID NULL,
    "JudulTicket" varchar(255) NOT NULL,
    "DeskripsiMasalah" TEXT NULL,
    "DibuatDariPesanId" UUID NULL,
    "DitugaskanKepada" UUID NULL,
    "TglDitugaskan" TIMESTAMP NULL,
    "TglTargetSelesai" TIMESTAMP NULL,
    "TglSelesai" TIMESTAMP NULL,
    "TglDitutup" TIMESTAMP NULL,
    "DitutupOleh" UUID NULL,
    "RingkasanAi" TEXT NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TTicket" PRIMARY KEY ("Id"),
    CONSTRAINT "UQ_TTicket_NomorTicket" UNIQUE ("NomorTicket"),
    CONSTRAINT "FK_TTicket_TChat" FOREIGN KEY ("IdChat") REFERENCES "TChat" ("Id"),
    CONSTRAINT "FK_TTicket_MCustomer" FOREIGN KEY ("IdCustomer") REFERENCES "MCustomer" ("Id"),
    CONSTRAINT "FK_TTicket_MInstansi" FOREIGN KEY ("IdInstansi") REFERENCES "MInstansi" ("Id"),
    CONSTRAINT "FK_TTicket_MKategoriTicket" FOREIGN KEY ("IdKategoriTicket") REFERENCES "MKategoriTicket" ("Id"),
    CONSTRAINT "FK_TTicket_MPrioritasTicket" FOREIGN KEY ("IdPrioritasTicket") REFERENCES "MPrioritasTicket" ("Id"),
    CONSTRAINT "FK_TTicket_MStatusTicket" FOREIGN KEY ("IdStatusTicket") REFERENCES "MStatusTicket" ("Id"),
    CONSTRAINT "FK_TTicket_TChatD" FOREIGN KEY ("DibuatDariPesanId") REFERENCES "TChatD" ("Id")
);

CREATE TABLE IF NOT EXISTS "TTicketD" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdTicket" UUID NOT NULL,
    "JenisAktivitas" varchar(100) NOT NULL,
    "IsiAktivitas" TEXT NULL,
    "StatusSebelum" varchar(100) NULL,
    "StatusSesudah" varchar(100) NULL,
    "DitujukanKepada" UUID NULL,
    "TglAktivitas" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TTicketD" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TTicketD_TTicket" FOREIGN KEY ("IdTicket") REFERENCES "TTicket" ("Id")
);

CREATE TABLE IF NOT EXISTS "TTicketDPenugasan" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdTicket" UUID NOT NULL,
    "DitugaskanDari" UUID NULL,
    "DitugaskanKepada" UUID NOT NULL,
    "AlasanPenugasan" varchar(500) NULL,
    "TglPenugasan" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TTicketDPenugasan" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TTicketDPenugasan_TTicket" FOREIGN KEY ("IdTicket") REFERENCES "TTicket" ("Id")
);

CREATE TABLE IF NOT EXISTS "TTicketDLampiran" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdTicket" UUID NOT NULL,
    "NamaFile" varchar(255) NOT NULL,
    "PathFile" varchar(1000) NOT NULL,
    "TipeFile" varchar(100) NULL,
    "UkuranFile" bigint NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TTicketDLampiran" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TTicketDLampiran_TTicket" FOREIGN KEY ("IdTicket") REFERENCES "TTicket" ("Id")
);

CREATE TABLE IF NOT EXISTS "TAiPermintaan" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdAiProvider" UUID NULL,
    "JenisPermintaan" varchar(100) NOT NULL,
    "ProviderAi" varchar(50) NOT NULL,
    "ModelAi" varchar(100) NULL,
    "IdChat" UUID NULL,
    "IdTicket" UUID NULL,
    "PromptRingkas" TEXT NULL,
    "PromptJson" TEXT NULL,
    "StatusPermintaan" varchar(50) NOT NULL DEFAULT 'Baru',
    "TglMulai" TIMESTAMP NULL,
    "TglSelesai" TIMESTAMP NULL,
    "PesanError" TEXT NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TAiPermintaan" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TAiPermintaan_MAiProvider" FOREIGN KEY ("IdAiProvider") REFERENCES "MAiProvider" ("Id"),
    CONSTRAINT "FK_TAiPermintaan_TChat" FOREIGN KEY ("IdChat") REFERENCES "TChat" ("Id"),
    CONSTRAINT "FK_TAiPermintaan_TTicket" FOREIGN KEY ("IdTicket") REFERENCES "TTicket" ("Id")
);

CREATE TABLE IF NOT EXISTS "TAiRespon" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
    "IdAiPermintaan" UUID NOT NULL,
    "JenisRespon" varchar(100) NOT NULL,
    "ResponRingkas" TEXT NULL,
    "ResponJson" TEXT NULL,
    "TokenInput" int NULL,
    "TokenOutput" int NULL,
    "BiayaEstimasi" decimal(18, 6) NULL,
    "DisetujuiOleh" UUID NULL,
    "TglDisetujui" TIMESTAMP NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "PK_TAiRespon" PRIMARY KEY ("Id"),
    CONSTRAINT "FK_TAiRespon_TAiPermintaan" FOREIGN KEY ("IdAiPermintaan") REFERENCES "TAiPermintaan" ("Id")
);

ALTER TABLE "TChatD" ADD CONSTRAINT "FK_TChatD_TAiRespon" FOREIGN KEY ("IdAiRespon") REFERENCES "TAiRespon"("Id");

CREATE INDEX IF NOT EXISTS  "IX_MCustomer_NamaCustomer" ON "MCustomer" ("NamaCustomer");
CREATE INDEX IF NOT EXISTS  "IX_MInstansi_NamaInstansi" ON "MInstansi" ("NamaInstansi");
CREATE INDEX IF NOT EXISTS  "IX_MNomorWhatsapp_NomorWhatsapp" ON "MNomorWhatsapp" ("NomorWhatsapp");
CREATE INDEX IF NOT EXISTS  "IX_MGrupWhatsapp_IdInstansi" ON "MGrupWhatsapp" ("IdInstansi");
CREATE INDEX IF NOT EXISTS  "IX_MGrupWhatsapp_IdGrupWaha" ON "MGrupWhatsapp" ("IdGrupWaha");
CREATE INDEX IF NOT EXISTS  "IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp" ON "MAnggotaGrupWhatsapp" ("IdGrupWhatsapp");
CREATE INDEX IF NOT EXISTS  "IX_MProdukCustomer_IdCustomer" ON "MProdukCustomer" ("IdCustomer");
CREATE INDEX IF NOT EXISTS  "IX_MHariLibur_TanggalLibur" ON "MHariLibur" ("TanggalLibur", "NonAktif");
CREATE INDEX IF NOT EXISTS  "IX_TLogAktivitas_TglAktivitas" ON "TLogAktivitas" ("TglAktivitas");
CREATE INDEX IF NOT EXISTS  "IX_TLogError_TglError" ON "TLogError" ("TglError");
CREATE INDEX IF NOT EXISTS  "IX_TLogIntegrasi_TglRequest" ON "TLogIntegrasi" ("TglRequest");
CREATE INDEX IF NOT EXISTS  "IX_TLogWebhookWaha_TglDiterima" ON "TLogWebhookWaha" ("TglDiterima");
CREATE INDEX IF NOT EXISTS  "IX_TLogWebhookWaha_SudahDiproses" ON "TLogWebhookWaha" ("SudahDiproses");
CREATE INDEX IF NOT EXISTS  "IX_TChat_NomorWhatsapp" ON "TChat" ("NomorWhatsapp");
CREATE INDEX IF NOT EXISTS  "IX_TChat_IdWahaTerdeteksi" ON "TChat" ("IdWahaTerdeteksi");
CREATE INDEX IF NOT EXISTS  "IX_TChat_NomorWhatsappTerdeteksi" ON "TChat" ("NomorWhatsappTerdeteksi");
CREATE INDEX IF NOT EXISTS  "IX_TChat_IdCustomer" ON "TChat" ("IdCustomer");
CREATE INDEX IF NOT EXISTS  "IX_TChat_IdInstansi" ON "TChat" ("IdInstansi");
CREATE INDEX IF NOT EXISTS  "IX_TChat_IdGrupWhatsapp" ON "TChat" ("IdGrupWhatsapp");
CREATE INDEX IF NOT EXISTS  "IX_TChat_IdStatusChat" ON "TChat" ("IdStatusChat");
CREATE INDEX IF NOT EXISTS  "IX_TChat_DitugaskanKepada" ON "TChat" ("DitugaskanKepada");
CREATE INDEX IF NOT EXISTS  "IX_TChat_TglChatTerakhir" ON "TChat" ("TglChatTerakhir");
CREATE INDEX IF NOT EXISTS  "IX_TChatD_IdChat_TglPesan" ON "TChatD" ("IdChat", "TglPesan");
CREATE INDEX IF NOT EXISTS  "IX_TChatD_IdPesanWaha" ON "TChatD" ("IdPesanWaha");
CREATE INDEX IF NOT EXISTS  "IX_TTicket_IdCustomer" ON "TTicket" ("IdCustomer");
CREATE INDEX IF NOT EXISTS  "IX_TTicket_IdInstansi" ON "TTicket" ("IdInstansi");
CREATE INDEX IF NOT EXISTS  "IX_TTicket_IdStatusTicket" ON "TTicket" ("IdStatusTicket");
CREATE INDEX IF NOT EXISTS  "IX_TTicket_DitugaskanKepada" ON "TTicket" ("DitugaskanKepada");
CREATE INDEX IF NOT EXISTS  "IX_TTicket_TglTargetSelesai" ON "TTicket" ("TglTargetSelesai");
CREATE INDEX IF NOT EXISTS  "IX_TTicketD_IdTicket_TglAktivitas" ON "TTicketD" ("IdTicket", "TglAktivitas");
CREATE INDEX IF NOT EXISTS  "IX_TAiPermintaan_IdChat" ON "TAiPermintaan" ("IdChat");
CREATE INDEX IF NOT EXISTS  "IX_TAiPermintaan_IdTicket" ON "TAiPermintaan" ("IdTicket");
CREATE INDEX IF NOT EXISTS  "IX_TChatD_IdAiRespon" ON "TChatD" ("IdAiRespon");

INSERT INTO "MPeran" (
    "KodePeran",
    "NamaPeran",
    "Keterangan"
)
VALUES ('ADMIN', 'Admin', 'Akses penuh aplikasi'),
('SUPERVISOR_CS', 'Supervisor CS', 'Monitoring dan pengaturan customer service'),
('CS', 'Customer Service', 'Menangani chat dan membuat ticket'),
('DEVELOPER', 'Developer', 'Menangani ticket teknis'),
('VIEWER', 'Viewer', 'Melihat dashboard dan laporan');

INSERT INTO "MStatusChat" (
    "KodeStatusChat",
    "NamaStatusChat",
    "Urutan",
    "Warna"
)
VALUES ('BARU', 'Baru', 10, 'info'),
('MENUNGGU_CS', 'Menunggu CS', 20, 'warning'),
('DALAM_PROSES', 'Dalam Proses', 30, 'primary'),
('MENUNGGU_CUSTOMER', 'Menunggu Customer', 40, 'gray'),
('SELESAI', 'Selesai', 50, 'success'),
('DITUTUP', 'Ditutup', 60, 'gray');

INSERT INTO "MStatusTicket" (
    "KodeStatusTicket",
    "NamaStatusTicket",
    "Urutan",
    "StatusFinal",
    "Warna"
)
VALUES ('DRAFT', 'Draft', 10, FALSE, 'gray'),
('BARU', 'Baru', 20, FALSE, 'info'),
('DIANALISA_CS', 'Dianalisa CS', 30, FALSE, 'warning'),
('BUTUH_DATA_CUSTOMER', 'Butuh Data Customer', 40, FALSE, 'warning'),
('DITERUSKAN_DEVELOPER', 'Diteruskan ke Developer', 50, FALSE, 'primary'),
('DALAM_PENGERJAAN', 'Dalam Pengerjaan', 60, FALSE, 'primary'),
('MENUNGGU_DEPLOY', 'Menunggu Deploy', 70, FALSE, 'warning'),
('SELESAI', 'Selesai', 80, TRUE, 'success'),
('DITUTUP', 'Ditutup', 90, TRUE, 'gray'),
('DIBATALKAN', 'Dibatalkan', 100, TRUE, 'danger');

INSERT INTO "MPrioritasTicket" (
    "KodePrioritas",
    "NamaPrioritas",
    "Urutan",
    "BatasSlaMenit",
    "Warna"
)
VALUES ('RENDAH', 'Rendah', 10, 4320, 'gray'),
('NORMAL', 'Normal', 20, 1440, 'info'),
('TINGGI', 'Tinggi', 30, 480, 'warning'),
('KRITIS', 'Kritis', 40, 120, 'danger');

INSERT INTO "MKategoriTicket" (
    "KodeKategori",
    "NamaKategori",
    "Keterangan"
)
VALUES ('BUG', 'Bug Aplikasi', 'Masalah error atau bug aplikasi'),
('DATA', 'Masalah Data', 'Masalah data master atau transaksi'),
('AKSES', 'Masalah Akses', 'Login, role, permission, atau akses menu'),
('REQUEST', 'Permintaan Fitur', 'Permintaan fitur baru atau perubahan fitur'),
('KONSULTASI', 'Konsultasi', 'Pertanyaan penggunaan aplikasi');

INSERT INTO "MPengaturanAi" (
    "KodePengaturan",
    "NamaPengaturan",
    "AutoReplyAktif",
    "AutoReplyDiluarJamKerja",
    "AutoReplyHariLibur",
    "AutoReplyJamKerjaSapaan",
    "AutoReplyJamKerjaBerlanjut",
    "JamKerjaMulai",
    "JamKerjaSelesai",
    "HariKerja",
    "ZonaWaktu",
    "ProviderAi",
    "ModelAi",
    "BaseUrl",
    "PromptSistem",
    "TemplateDiluarJamKerja",
    "TemplateHariLibur",
    "TemplateJamKerjaSapaan",
    "TemplateFallback",
    "NotifikasiChatBelumTerbalasAktif",
    "MenitTungguNotifikasi",
    "JedaNotifikasiMenit",
    "KodePeranPenerimaNotifikasi",
    "TemplateNotifikasiChatBelumTerbalas",
    "BatasRiwayatPesan",
    "KirimKeWaha",
    "ModeKirim"
)
VALUES (
    'DEFAULT',
    'Pengaturan Default AI Agent',
    FALSE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    '08:00',
    '17:00',
    '1,2,3,4,5',
    'Asia/Jakarta',
    'OpenAI',
    'gpt-5',
    'https://api.openai.com/v1/responses',
    'Anda adalah AI Agent customer service Care Desk. Jawab dalam Bahasa Indonesia yang sopan, singkat, jelas, dan jangan membuat janji teknis yang belum dipastikan. Jika masalah perlu ditangani manusia, arahkan bahwa tim customer service akan menindaklanjuti.',
    'Terima kasih sudah menghubungi Care Desk. Saat ini kami berada di luar jam operasional. Pesan Bapak/Ibu sudah kami terima dan akan kami tindak lanjuti pada jam kerja berikutnya.',
    'Terima kasih sudah menghubungi Care Desk. Hari ini kami sedang libur ({nama_hari_libur}). Pesan Bapak/Ibu tetap kami terima dan akan kami teruskan ke tim customer service. Silakan sampaikan detail kendalanya agar tim kami bisa menindaklanjuti pada hari kerja berikutnya, {tanggal_masuk_kerja}. Mohon maaf atas ketidaknyamanannya.',
    'Halo, terima kasih sudah menghubungi Care Desk. Saya bantu catat terlebih dahulu ya. Silakan jelaskan kendala yang sedang dialami, nanti tim customer service kami akan melanjutkan penanganannya.',
    'Terima kasih informasinya. Pesan sudah kami terima dan akan kami teruskan ke tim terkait untuk ditindaklanjuti.',
    TRUE,
    10,
    30,
    'ADMIN,SUPERVISOR_CS,CS',
    'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek Care Desk: {url_admin}',
    8,
    FALSE,
    'DraftLokal'
);

CREATE TABLE IF NOT EXISTS "TChatbotInternal" (
        "Id" UUID NOT NULL DEFAULT gen_random_uuid(),
        "IdPengguna" UUID NOT NULL,
        "PeranPengirim" varchar(20) NOT NULL,
        "IsiPesan" TEXT NOT NULL,
        "IdAiRespon" UUID NULL,
        "KonteksJson" TEXT NULL,
        "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT "PK_TChatbotInternal" PRIMARY KEY ("Id"),
        CONSTRAINT "FK_TChatbotInternal_MPengguna" FOREIGN KEY ("IdPengguna") REFERENCES "MPengguna" ("Id"),
        CONSTRAINT "CK_TChatbotInternal_Peran" CHECK ("PeranPengirim" IN ('user', 'assistant'))
    );

CREATE INDEX IF NOT EXISTS  "IX_TChatbotInternal_Pengguna_Tgl" ON "TChatbotInternal" ("IdPengguna", "TglBuat" DESC);
CREATE INDEX IF NOT EXISTS  "IX_TChatD_IdPesanWaha_Partial" ON "TChatD" ("IdPesanWaha") WHERE "IdPesanWaha" IS NOT NULL;
CREATE INDEX IF NOT EXISTS  "IX_TChatD_Arah_Dikirim_Tgl" ON "TChatD" ("ArahPesan", "DikirimOlehCustomer", "TglPesan" DESC) INCLUDE ("IdChat", "IsiPesan");
CREATE INDEX IF NOT EXISTS  "IX_TChatD_IdChat_Arah_Ai_Tgl" ON "TChatD" ("IdChat", "ArahPesan", "DihasilkanOlehAi", "TglPesan" DESC);
CREATE INDEX IF NOT EXISTS  "IX_TChatD_TglPesan_Arah_Status" ON "TChatD" ("TglPesan") INCLUDE ("IdChat", "ArahPesan", "DihasilkanOlehAi", "StatusKirim");

-- Task module additions (2026-07-13)
CREATE TABLE IF NOT EXISTS "MStatusTask" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "KodeStatusTask" varchar(50) NOT NULL UNIQUE,
    "NamaStatusTask" varchar(100) NOT NULL,
    "Urutan" int NOT NULL DEFAULT 0,
    "StatusFinal" BOOLEAN NOT NULL DEFAULT FALSE,
    "Warna" varchar(30) NULL,
    "NonAktif" BOOLEAN NOT NULL DEFAULT FALSE,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL
);

CREATE TABLE IF NOT EXISTS "TTask" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "NomorTask" varchar(50) NOT NULL UNIQUE,
    "IdTicket" UUID NULL REFERENCES "TTicket" ("Id"),
    "IdChat" UUID NULL REFERENCES "TChat" ("Id"),
    "IdCustomer" UUID NULL REFERENCES "MCustomer" ("Id"),
    "IdInstansi" UUID NULL REFERENCES "MInstansi" ("Id"),
    "IdTugasInduk" UUID NULL,
    "IdKategoriTicket" UUID NULL REFERENCES "MKategoriTicket" ("Id"),
    "IdPrioritasTicket" UUID NULL REFERENCES "MPrioritasTicket" ("Id"),
    "IdStatusTask" UUID NOT NULL REFERENCES "MStatusTask" ("Id"),
    "JudulTask" varchar(255) NOT NULL,
    "DeskripsiTask" TEXT NULL,
    "DitugaskanKepada" UUID NULL REFERENCES "MPengguna" ("Id"),
    "TglDitugaskan" TIMESTAMP NULL,
    "TglTargetSelesai" TIMESTAMP NULL,
    "TglSelesai" TIMESTAMP NULL,
    "TglDitutup" TIMESTAMP NULL,
    "DitutupOleh" UUID NULL,
    "EstimasiMenit" int NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL,
    CONSTRAINT "FK_TTask_TTask_Induk" FOREIGN KEY ("IdTugasInduk") REFERENCES "TTask" ("Id")
);

CREATE TABLE IF NOT EXISTS "TTaskDPenugasan" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "IdTask" UUID NOT NULL REFERENCES "TTask" ("Id"),
    "DitugaskanDari" UUID NULL REFERENCES "MPengguna" ("Id"),
    "DitugaskanKepada" UUID NOT NULL REFERENCES "MPengguna" ("Id"),
    "AlasanPenugasan" varchar(500) NULL,
    "TglPenugasan" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL
);

CREATE TABLE IF NOT EXISTS "TTaskDChecklist" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "IdTask" UUID NOT NULL REFERENCES "TTask" ("Id"),
    "JudulItem" varchar(500) NOT NULL,
    "Selesai" BOOLEAN NOT NULL DEFAULT FALSE,
    "Urutan" int NOT NULL DEFAULT 0,
    "TglSelesai" TIMESTAMP NULL,
    "DiselesaikanOleh" UUID NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL
);

CREATE TABLE IF NOT EXISTS "TTaskDKomentar" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "IdTask" UUID NOT NULL REFERENCES "TTask" ("Id"),
    "IsiKomentar" TEXT NOT NULL,
    "TglKomentar" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL
);

CREATE TABLE IF NOT EXISTS "TTaskDLampiran" (
    "Id" UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
    "IdTask" UUID NOT NULL REFERENCES "TTask" ("Id"),
    "NamaFile" varchar(255) NOT NULL,
    "PathFile" varchar(1000) NOT NULL,
    "TipeFile" varchar(100) NULL,
    "UkuranFile" bigint NULL,
    "TglBuat" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "DibuatOleh" UUID NULL,
    "TglEdit" TIMESTAMP NULL,
    "DieditOleh" UUID NULL
);

CREATE TABLE IF NOT EXISTS "notifications" (
    "id" UUID NOT NULL PRIMARY KEY,
    "type" VARCHAR(255) NOT NULL,
    "notifiable_type" VARCHAR(255) NOT NULL,
    "notifiable_id" UUID NOT NULL,
    "data" TEXT NOT NULL,
    "read_at" TIMESTAMP NULL,
    "created_at" TIMESTAMP NULL,
    "updated_at" TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS  "notifications_notifiable_type_notifiable_id_index" ON "notifications" ("notifiable_type", "notifiable_id");

CREATE TABLE IF NOT EXISTS "MNomorDokumen" (
    "Kode" varchar(32) NOT NULL PRIMARY KEY,
    "Nilai" int NOT NULL DEFAULT 0,
    "TglEdit" TIMESTAMP NULL
);