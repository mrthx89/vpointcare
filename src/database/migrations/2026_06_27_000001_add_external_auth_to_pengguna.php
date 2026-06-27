<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('External auth migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
SET XACT_ABORT ON;

BEGIN TRANSACTION;

IF COL_LENGTH('MPengguna', 'IdPeran') IS NOT NULL
BEGIN
    ALTER TABLE MPengguna ALTER COLUMN IdPeran uniqueidentifier NULL;
END;

IF COL_LENGTH('MPengguna', 'StatusRegistrasi') IS NULL
BEGIN
    ALTER TABLE MPengguna ADD StatusRegistrasi varchar(30) NOT NULL CONSTRAINT DF_MPengguna_StatusRegistrasi DEFAULT 'approved';
END;

IF COL_LENGTH('MPengguna', 'RegistrasiExternalProvider') IS NULL
BEGIN
    ALTER TABLE MPengguna ADD RegistrasiExternalProvider varchar(50) NULL;
END;

IF COL_LENGTH('MPengguna', 'RegistrasiExternalPada') IS NULL
BEGIN
    ALTER TABLE MPengguna ADD RegistrasiExternalPada datetime2 NULL;
END;

IF OBJECT_ID(N'MPenggunaExternalIdentity', 'U') IS NULL
BEGIN
    CREATE TABLE MPenggunaExternalIdentity (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_MPenggunaExternalIdentity_Id DEFAULT NEWSEQUENTIALID(),
        IdPengguna uniqueidentifier NOT NULL,
        Provider varchar(50) NOT NULL,
        ProviderUserId nvarchar(255) NOT NULL,
        Email varchar(150) NOT NULL,
        EmailTerverifikasi bit NOT NULL CONSTRAINT DF_MPenggunaExternalIdentity_EmailTerverifikasi DEFAULT 0,
        Nama nvarchar(150) NULL,
        AvatarUrl nvarchar(500) NULL,
        Metadata nvarchar(max) NULL,
        TglTaut datetime2 NOT NULL CONSTRAINT DF_MPenggunaExternalIdentity_TglTaut DEFAULT SYSDATETIME(),
        LoginTerakhirPada datetime2 NULL,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_MPenggunaExternalIdentity_TglBuat DEFAULT SYSDATETIME(),
        TglEdit datetime2 NULL,
        CONSTRAINT PK_MPenggunaExternalIdentity PRIMARY KEY (Id),
        CONSTRAINT FK_MPenggunaExternalIdentity_MPengguna FOREIGN KEY (IdPengguna) REFERENCES MPengguna(Id) ON DELETE CASCADE
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MPenggunaExternalIdentity_ProviderUser' AND object_id = OBJECT_ID('MPenggunaExternalIdentity'))
BEGIN
    CREATE UNIQUE INDEX UX_MPenggunaExternalIdentity_ProviderUser ON MPenggunaExternalIdentity (Provider, ProviderUserId);
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MPenggunaExternalIdentity_ProviderEmail' AND object_id = OBJECT_ID('MPenggunaExternalIdentity'))
BEGIN
    CREATE INDEX IX_MPenggunaExternalIdentity_ProviderEmail ON MPenggunaExternalIdentity (Provider, Email);
END;

COMMIT TRANSACTION;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPenggunaExternalIdentity', 'U') IS NOT NULL
    DROP TABLE MPenggunaExternalIdentity;

IF COL_LENGTH('MPengguna', 'RegistrasiExternalPada') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN RegistrasiExternalPada;

IF COL_LENGTH('MPengguna', 'RegistrasiExternalProvider') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN RegistrasiExternalProvider;

IF COL_LENGTH('MPengguna', 'StatusRegistrasi') IS NOT NULL
    ALTER TABLE MPengguna DROP CONSTRAINT DF_MPengguna_StatusRegistrasi;

IF COL_LENGTH('MPengguna', 'StatusRegistrasi') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN StatusRegistrasi;
SQL);
    }
};
