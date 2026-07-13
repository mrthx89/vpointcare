<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID('MStatusTask', 'U') IS NULL
BEGIN
    CREATE TABLE MStatusTask (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_MStatusTask_Id DEFAULT NEWSEQUENTIALID(),
        KodeStatusTask varchar(50) NOT NULL,
        NamaStatusTask varchar(100) NOT NULL,
        Urutan int NOT NULL CONSTRAINT DF_MStatusTask_Urutan DEFAULT 0,
        StatusFinal bit NOT NULL CONSTRAINT DF_MStatusTask_StatusFinal DEFAULT 0,
        Warna varchar(30) NULL,
        NonAktif bit NOT NULL CONSTRAINT DF_MStatusTask_NonAktif DEFAULT 0,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_MStatusTask_TglBuat DEFAULT SYSDATETIME(),
        DibuatOleh uniqueidentifier NULL, TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL,
        CONSTRAINT PK_MStatusTask PRIMARY KEY (Id),
        CONSTRAINT UQ_MStatusTask_KodeStatusTask UNIQUE (KodeStatusTask)
    );
END;

IF OBJECT_ID('TTask', 'U') IS NULL
BEGIN
    CREATE TABLE TTask (
        Id uniqueidentifier NOT NULL CONSTRAINT DF_TTask_Id DEFAULT NEWSEQUENTIALID(),
        NomorTask varchar(50) NOT NULL, IdTicket uniqueidentifier NULL, IdChat uniqueidentifier NULL,
        IdCustomer uniqueidentifier NULL, IdInstansi uniqueidentifier NULL, IdTugasInduk uniqueidentifier NULL,
        IdKategoriTicket uniqueidentifier NULL, IdPrioritasTicket uniqueidentifier NULL, IdStatusTask uniqueidentifier NOT NULL,
        JudulTask varchar(255) NOT NULL, DeskripsiTask nvarchar(max) NULL, DitugaskanKepada uniqueidentifier NULL,
        TglDitugaskan datetime2 NULL, TglTargetSelesai datetime2 NULL, TglSelesai datetime2 NULL,
        TglDitutup datetime2 NULL, DitutupOleh uniqueidentifier NULL, EstimasiMenit int NULL,
        TglBuat datetime2 NOT NULL CONSTRAINT DF_TTask_TglBuat DEFAULT SYSDATETIME(), DibuatOleh uniqueidentifier NULL,
        TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL,
        CONSTRAINT PK_TTask PRIMARY KEY (Id), CONSTRAINT UQ_TTask_NomorTask UNIQUE (NomorTask),
        CONSTRAINT FK_TTask_TTicket FOREIGN KEY (IdTicket) REFERENCES TTicket(Id),
        CONSTRAINT FK_TTask_TChat FOREIGN KEY (IdChat) REFERENCES TChat(Id),
        CONSTRAINT FK_TTask_MCustomer FOREIGN KEY (IdCustomer) REFERENCES MCustomer(Id),
        CONSTRAINT FK_TTask_MInstansi FOREIGN KEY (IdInstansi) REFERENCES MInstansi(Id),
        CONSTRAINT FK_TTask_TTask_Induk FOREIGN KEY (IdTugasInduk) REFERENCES TTask(Id),
        CONSTRAINT FK_TTask_MKategoriTicket FOREIGN KEY (IdKategoriTicket) REFERENCES MKategoriTicket(Id),
        CONSTRAINT FK_TTask_MPrioritasTicket FOREIGN KEY (IdPrioritasTicket) REFERENCES MPrioritasTicket(Id),
        CONSTRAINT FK_TTask_MStatusTask FOREIGN KEY (IdStatusTask) REFERENCES MStatusTask(Id),
        CONSTRAINT FK_TTask_MPengguna_Assignee FOREIGN KEY (DitugaskanKepada) REFERENCES MPengguna(Id)
    );
    CREATE INDEX IX_TTask_IdTicket ON TTask(IdTicket); CREATE INDEX IX_TTask_IdStatusTask ON TTask(IdStatusTask);
    CREATE INDEX IX_TTask_IdChat ON TTask(IdChat); CREATE INDEX IX_TTask_IdCustomer ON TTask(IdCustomer);
    CREATE INDEX IX_TTask_DitugaskanKepada ON TTask(DitugaskanKepada); CREATE INDEX IX_TTask_TglTargetSelesai ON TTask(TglTargetSelesai); CREATE INDEX IX_TTask_IdTugasInduk ON TTask(IdTugasInduk);
END;

IF OBJECT_ID('TTaskDPenugasan', 'U') IS NULL
BEGIN
    CREATE TABLE TTaskDPenugasan (Id uniqueidentifier NOT NULL CONSTRAINT DF_TTaskDPenugasan_Id DEFAULT NEWSEQUENTIALID(), IdTask uniqueidentifier NOT NULL, DitugaskanDari uniqueidentifier NULL, DitugaskanKepada uniqueidentifier NOT NULL, AlasanPenugasan varchar(500) NULL, TglPenugasan datetime2 NOT NULL CONSTRAINT DF_TTaskDPenugasan_Tgl DEFAULT SYSDATETIME(), TglBuat datetime2 NOT NULL CONSTRAINT DF_TTaskDPenugasan_Buat DEFAULT SYSDATETIME(), DibuatOleh uniqueidentifier NULL, TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL, CONSTRAINT PK_TTaskDPenugasan PRIMARY KEY (Id), CONSTRAINT FK_TTaskDPenugasan_TTask FOREIGN KEY (IdTask) REFERENCES TTask(Id), CONSTRAINT FK_TTaskDPenugasan_MPengguna_Dari FOREIGN KEY (DitugaskanDari) REFERENCES MPengguna(Id), CONSTRAINT FK_TTaskDPenugasan_MPengguna_Kepada FOREIGN KEY (DitugaskanKepada) REFERENCES MPengguna(Id));
    CREATE INDEX IX_TTaskDPenugasan_IdTask ON TTaskDPenugasan(IdTask);
END;
IF OBJECT_ID('TTaskDChecklist', 'U') IS NULL
BEGIN
    CREATE TABLE TTaskDChecklist (Id uniqueidentifier NOT NULL CONSTRAINT DF_TTaskDChecklist_Id DEFAULT NEWSEQUENTIALID(), IdTask uniqueidentifier NOT NULL, JudulItem varchar(500) NOT NULL, Selesai bit NOT NULL CONSTRAINT DF_TTaskDChecklist_Selesai DEFAULT 0, Urutan int NOT NULL CONSTRAINT DF_TTaskDChecklist_Urutan DEFAULT 0, TglSelesai datetime2 NULL, DiselesaikanOleh uniqueidentifier NULL, TglBuat datetime2 NOT NULL CONSTRAINT DF_TTaskDChecklist_Buat DEFAULT SYSDATETIME(), DibuatOleh uniqueidentifier NULL, TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL, CONSTRAINT PK_TTaskDChecklist PRIMARY KEY (Id), CONSTRAINT FK_TTaskDChecklist_TTask FOREIGN KEY (IdTask) REFERENCES TTask(Id));
    CREATE INDEX IX_TTaskDChecklist_IdTask ON TTaskDChecklist(IdTask);
END;
IF OBJECT_ID('TTaskDKomentar', 'U') IS NULL
BEGIN
    CREATE TABLE TTaskDKomentar (Id uniqueidentifier NOT NULL CONSTRAINT DF_TTaskDKomentar_Id DEFAULT NEWSEQUENTIALID(), IdTask uniqueidentifier NOT NULL, IsiKomentar nvarchar(max) NOT NULL, TglKomentar datetime2 NOT NULL CONSTRAINT DF_TTaskDKomentar_Tgl DEFAULT SYSDATETIME(), TglBuat datetime2 NOT NULL CONSTRAINT DF_TTaskDKomentar_Buat DEFAULT SYSDATETIME(), DibuatOleh uniqueidentifier NULL, TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL, CONSTRAINT PK_TTaskDKomentar PRIMARY KEY (Id), CONSTRAINT FK_TTaskDKomentar_TTask FOREIGN KEY (IdTask) REFERENCES TTask(Id));
    CREATE INDEX IX_TTaskDKomentar_IdTask ON TTaskDKomentar(IdTask);
END;
IF OBJECT_ID('TTaskDLampiran', 'U') IS NULL
BEGIN
    CREATE TABLE TTaskDLampiran (Id uniqueidentifier NOT NULL CONSTRAINT DF_TTaskDLampiran_Id DEFAULT NEWSEQUENTIALID(), IdTask uniqueidentifier NOT NULL, NamaFile varchar(255) NOT NULL, PathFile varchar(1000) NOT NULL, TipeFile varchar(100) NULL, UkuranFile bigint NULL, TglBuat datetime2 NOT NULL CONSTRAINT DF_TTaskDLampiran_Buat DEFAULT SYSDATETIME(), DibuatOleh uniqueidentifier NULL, TglEdit datetime2 NULL, DieditOleh uniqueidentifier NULL, CONSTRAINT PK_TTaskDLampiran PRIMARY KEY (Id), CONSTRAINT FK_TTaskDLampiran_TTask FOREIGN KEY (IdTask) REFERENCES TTask(Id));
    CREATE INDEX IX_TTaskDLampiran_IdTask ON TTaskDLampiran(IdTask);
END;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::unprepared('DROP TABLE IF EXISTS TTaskDLampiran, TTaskDKomentar, TTaskDChecklist, TTaskDPenugasan, TTask, MStatusTask;');
        }
    }
};
