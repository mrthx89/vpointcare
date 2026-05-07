<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The WhatsApp group mapping migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MGrupWhatsapp', 'U') IS NULL
BEGIN
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
END
SQL);

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MAnggotaGrupWhatsapp', 'U') IS NULL
BEGIN
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
END
SQL);

        foreach ([
            'IdGrupWhatsapp uniqueidentifier NULL',
            "JenisChat varchar(30) NOT NULL CONSTRAINT DF_TChat_JenisChat DEFAULT 'Pribadi'",
            'NamaGrupWhatsapp varchar(200) NULL',
        ] as $definition) {
            [$column] = explode(' ', $definition, 2);
            DB::unprepared("
                IF COL_LENGTH('TChat', '{$column}') IS NULL
                    ALTER TABLE TChat ADD {$definition}
            ");
        }

        DB::unprepared(<<<'SQL'
IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_TChat_MGrupWhatsapp'
)
BEGIN
    ALTER TABLE TChat
    ADD CONSTRAINT FK_TChat_MGrupWhatsapp FOREIGN KEY (IdGrupWhatsapp) REFERENCES MGrupWhatsapp(Id);
END
SQL);

        foreach ([
            'PengirimNomorWhatsapp varchar(30) NULL',
            'PengirimNamaKontak varchar(150) NULL',
        ] as $definition) {
            [$column] = explode(' ', $definition, 2);
            DB::unprepared("
                IF COL_LENGTH('TChatD', '{$column}') IS NULL
                    ALTER TABLE TChatD ADD {$definition}
            ");
        }

        DB::unprepared("IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MGrupWhatsapp_IdInstansi') CREATE INDEX IX_MGrupWhatsapp_IdInstansi ON MGrupWhatsapp (IdInstansi)");
        DB::unprepared("IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MGrupWhatsapp_IdGrupWaha') CREATE INDEX IX_MGrupWhatsapp_IdGrupWaha ON MGrupWhatsapp (IdGrupWaha)");
        DB::unprepared("IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp') CREATE INDEX IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp ON MAnggotaGrupWhatsapp (IdGrupWhatsapp)");
        DB::unprepared("IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChat_IdGrupWhatsapp') CREATE INDEX IX_TChat_IdGrupWhatsapp ON TChat (IdGrupWhatsapp)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared("IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_TChat_MGrupWhatsapp') ALTER TABLE TChat DROP CONSTRAINT FK_TChat_MGrupWhatsapp");
        DB::unprepared("IF COL_LENGTH('TChatD', 'PengirimNamaKontak') IS NOT NULL ALTER TABLE TChatD DROP COLUMN PengirimNamaKontak");
        DB::unprepared("IF COL_LENGTH('TChatD', 'PengirimNomorWhatsapp') IS NOT NULL ALTER TABLE TChatD DROP COLUMN PengirimNomorWhatsapp");
        DB::unprepared("IF COL_LENGTH('TChat', 'NamaGrupWhatsapp') IS NOT NULL ALTER TABLE TChat DROP COLUMN NamaGrupWhatsapp");
        DB::unprepared("IF COL_LENGTH('TChat', 'JenisChat') IS NOT NULL ALTER TABLE TChat DROP CONSTRAINT DF_TChat_JenisChat; IF COL_LENGTH('TChat', 'JenisChat') IS NOT NULL ALTER TABLE TChat DROP COLUMN JenisChat");
        DB::unprepared("IF COL_LENGTH('TChat', 'IdGrupWhatsapp') IS NOT NULL ALTER TABLE TChat DROP COLUMN IdGrupWhatsapp");
        DB::unprepared("IF OBJECT_ID(N'MAnggotaGrupWhatsapp', 'U') IS NOT NULL DROP TABLE MAnggotaGrupWhatsapp");
        DB::unprepared("IF OBJECT_ID(N'MGrupWhatsapp', 'U') IS NOT NULL DROP TABLE MGrupWhatsapp");
    }
};
