<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The unanswered chat notification migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengaturanAi', 'NotifikasiChatBelumTerbalasAktif') IS NULL
    ALTER TABLE MPengaturanAi ADD NotifikasiChatBelumTerbalasAktif bit NOT NULL CONSTRAINT DF_MPengaturanAi_NotifikasiChatBelumTerbalasAktif DEFAULT 1;

IF COL_LENGTH('MPengaturanAi', 'MenitTungguNotifikasi') IS NULL
    ALTER TABLE MPengaturanAi ADD MenitTungguNotifikasi int NOT NULL CONSTRAINT DF_MPengaturanAi_MenitTungguNotifikasi DEFAULT 10;

IF COL_LENGTH('MPengaturanAi', 'JedaNotifikasiMenit') IS NULL
    ALTER TABLE MPengaturanAi ADD JedaNotifikasiMenit int NOT NULL CONSTRAINT DF_MPengaturanAi_JedaNotifikasiMenit DEFAULT 30;

IF COL_LENGTH('MPengaturanAi', 'KodePeranPenerimaNotifikasi') IS NULL
    ALTER TABLE MPengaturanAi ADD KodePeranPenerimaNotifikasi varchar(200) NOT NULL CONSTRAINT DF_MPengaturanAi_KodePeranPenerimaNotifikasi DEFAULT 'ADMIN,SUPERVISOR_CS,CS';

IF COL_LENGTH('MPengaturanAi', 'TemplateNotifikasiChatBelumTerbalas') IS NULL
    ALTER TABLE MPengaturanAi ADD TemplateNotifikasiChatBelumTerbalas nvarchar(max) NULL;

IF COL_LENGTH('TChat', 'TglNotifikasiBelumTerbalasTerakhir') IS NULL
    ALTER TABLE TChat ADD TglNotifikasiBelumTerbalasTerakhir datetime2 NULL;

IF COL_LENGTH('TChat', 'JumlahNotifikasiBelumTerbalas') IS NULL
    ALTER TABLE TChat ADD JumlahNotifikasiBelumTerbalas int NOT NULL CONSTRAINT DF_TChat_JumlahNotifikasiBelumTerbalas DEFAULT 0;
SQL);

        DB::unprepared(<<<'SQL'
UPDATE MPengaturanAi
SET TemplateNotifikasiChatBelumTerbalas = N'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}',
    TglEdit = SYSDATETIME()
WHERE KodePengaturan = 'DEFAULT'
  AND TemplateNotifikasiChatBelumTerbalas IS NULL;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('TChat', 'JumlahNotifikasiBelumTerbalas') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_TChat_JumlahNotifikasiBelumTerbalas', 'D') IS NOT NULL
        ALTER TABLE TChat DROP CONSTRAINT DF_TChat_JumlahNotifikasiBelumTerbalas;
    ALTER TABLE TChat DROP COLUMN JumlahNotifikasiBelumTerbalas;
END

IF COL_LENGTH('TChat', 'TglNotifikasiBelumTerbalasTerakhir') IS NOT NULL
    ALTER TABLE TChat DROP COLUMN TglNotifikasiBelumTerbalasTerakhir;

IF COL_LENGTH('MPengaturanAi', 'TemplateNotifikasiChatBelumTerbalas') IS NOT NULL
    ALTER TABLE MPengaturanAi DROP COLUMN TemplateNotifikasiChatBelumTerbalas;

IF COL_LENGTH('MPengaturanAi', 'KodePeranPenerimaNotifikasi') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_MPengaturanAi_KodePeranPenerimaNotifikasi', 'D') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_KodePeranPenerimaNotifikasi;
    ALTER TABLE MPengaturanAi DROP COLUMN KodePeranPenerimaNotifikasi;
END

IF COL_LENGTH('MPengaturanAi', 'JedaNotifikasiMenit') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_MPengaturanAi_JedaNotifikasiMenit', 'D') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_JedaNotifikasiMenit;
    ALTER TABLE MPengaturanAi DROP COLUMN JedaNotifikasiMenit;
END

IF COL_LENGTH('MPengaturanAi', 'MenitTungguNotifikasi') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_MPengaturanAi_MenitTungguNotifikasi', 'D') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_MenitTungguNotifikasi;
    ALTER TABLE MPengaturanAi DROP COLUMN MenitTungguNotifikasi;
END

IF COL_LENGTH('MPengaturanAi', 'NotifikasiChatBelumTerbalasAktif') IS NOT NULL
BEGIN
    IF OBJECT_ID(N'DF_MPengaturanAi_NotifikasiChatBelumTerbalasAktif', 'D') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP CONSTRAINT DF_MPengaturanAi_NotifikasiChatBelumTerbalasAktif;
    ALTER TABLE MPengaturanAi DROP COLUMN NotifikasiChatBelumTerbalasAktif;
END
SQL);
    }
};
