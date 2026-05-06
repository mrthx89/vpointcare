<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        if (Schema::hasColumn('MHakAkses', 'GroupSortOrder')) {
            DB::unprepared('ALTER TABLE MHakAkses DROP COLUMN [GroupSortOrder]');
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.key_constraints WHERE name = 'UQ_MHakAkses_KodeHakAkses' AND parent_object_id = OBJECT_ID('MHakAkses'))
    ALTER TABLE MHakAkses DROP CONSTRAINT UQ_MHakAkses_KodeHakAkses;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MHakAkses_KodeHakAkses_NotNull' AND object_id = OBJECT_ID('MHakAkses'))
    DROP INDEX UX_MHakAkses_KodeHakAkses_NotNull ON MHakAkses;

IF COL_LENGTH('MHakAkses', 'KodeHakAkses') IS NOT NULL
    ALTER TABLE MHakAkses ALTER COLUMN KodeHakAkses varchar(100) NULL;

CREATE UNIQUE INDEX UX_MHakAkses_KodeHakAkses_NotNull
ON MHakAkses (KodeHakAkses)
WHERE KodeHakAkses IS NOT NULL;
SQL);

        if (! Schema::hasColumn('MHakAkses', 'IdHakAkses')) {
            DB::unprepared('ALTER TABLE MHakAkses ADD [IdHakAkses] uniqueidentifier NULL');
            DB::unprepared(
                'ALTER TABLE MHakAkses ADD CONSTRAINT FK_MHakAkses_IdHakAkses '
                . 'FOREIGN KEY ([IdHakAkses]) REFERENCES [MHakAkses]([Id])'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        if (Schema::hasColumn('MHakAkses', 'IdHakAkses')) {
            DB::unprepared(
                "IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_MHakAkses_IdHakAkses') "
                . 'ALTER TABLE MHakAkses DROP CONSTRAINT FK_MHakAkses_IdHakAkses'
            );
            DB::unprepared('ALTER TABLE MHakAkses DROP COLUMN [IdHakAkses]');
        }

        DB::unprepared(<<<'SQL'
IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MHakAkses_KodeHakAkses_NotNull' AND object_id = OBJECT_ID('MHakAkses'))
    DROP INDEX UX_MHakAkses_KodeHakAkses_NotNull ON MHakAkses;
SQL);
    }
};
