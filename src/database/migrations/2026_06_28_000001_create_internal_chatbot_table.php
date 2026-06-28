<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The internal chatbot migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
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
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_TChatbotInternal_Pengguna_Tgl' AND object_id = OBJECT_ID('TChatbotInternal'))
    CREATE INDEX IX_TChatbotInternal_Pengguna_Tgl ON TChatbotInternal (IdPengguna, TglBuat DESC);
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'TChatbotInternal', 'U') IS NOT NULL
    DROP TABLE TChatbotInternal;
SQL);
    }
};
