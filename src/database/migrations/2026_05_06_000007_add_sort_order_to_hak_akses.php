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

        $columns = [
            ['SortOrder', 'int NULL'],
            ['IconString', 'varchar(100) NULL'],
        ];

        foreach ($columns as [$col, $def]) {
            if (! Schema::hasColumn('MHakAkses', $col)) {
                DB::unprepared("ALTER TABLE MHakAkses ADD [{$col}] {$def}");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        foreach (['IconString', 'SortOrder'] as $col) {
            if (Schema::hasColumn('MHakAkses', $col)) {
                DB::unprepared("ALTER TABLE MHakAkses DROP COLUMN [{$col}]");
            }
        }
    }
};
