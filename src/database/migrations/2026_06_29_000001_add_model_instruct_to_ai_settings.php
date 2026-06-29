<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MPengaturanAi')) {
            return;
        }

        if (Schema::hasColumn('MPengaturanAi', 'ModelInstructAi')) {
            return;
        }

        DB::statement(<<<SQL
IF OBJECT_ID(N'MPengaturanAi', 'U') IS NOT NULL AND COL_LENGTH(N'MPengaturanAi', 'ModelInstructAi') IS NULL
    ALTER TABLE MPengaturanAi ADD ModelInstructAi nvarchar(100) NULL;
SQL
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('MPengaturanAi')) {
            return;
        }

        if (! Schema::hasColumn('MPengaturanAi', 'ModelInstructAi')) {
            return;
        }

        DB::statement(<<<SQL
ALTER TABLE MPengaturanAi DROP COLUMN ModelInstructAi;
SQL
        );
    }
};
