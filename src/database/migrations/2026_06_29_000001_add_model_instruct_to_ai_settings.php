<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'ModelInstructAi') IS NULL
        ALTER TABLE MPengaturanAi ADD ModelInstructAi nvarchar(100) NULL;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('MPengaturanAi', 'ModelInstructAi') IS NOT NULL
        ALTER TABLE MPengaturanAi DROP COLUMN ModelInstructAi;
END
SQL);
    }
};
