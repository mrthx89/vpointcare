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

        foreach ($columns as [$column, $definition]) {
            if (! Schema::hasColumn('MHakAkses', $column)) {
                DB::unprepared("ALTER TABLE MHakAkses ADD [{$column}] {$definition}");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        if (Schema::hasColumn('MHakAkses', 'IconString')) {
            DB::unprepared('ALTER TABLE MHakAkses DROP COLUMN [IconString]');
        }
    }
};
