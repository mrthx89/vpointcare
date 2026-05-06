<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('This migration requires the sqlsrv database connection.');
        }

        if (! Schema::hasTable('MHakAkses')) {
            return;
        }

        $columns = [
            ['NamaHakAksesId', "varchar(150) NOT NULL DEFAULT ''"],
            ['NamaHakAksesEn', "varchar(150) NOT NULL DEFAULT ''"],
            ['ModulId', "varchar(100) NOT NULL DEFAULT ''"],
            ['ModulEn', "varchar(100) NOT NULL DEFAULT ''"],
            ['KeteranganId', "varchar(255) NULL"],
            ['KeteranganEn', "varchar(255) NULL"],
        ];

        foreach ($columns as [$col, $def]) {
            if (! Schema::hasColumn('MHakAkses', $col)) {
                DB::unprepared("ALTER TABLE MHakAkses ADD [{$col}] {$def}");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv' || ! Schema::hasTable('MHakAkses')) {
            return;
        }

        $columns = ['NamaHakAksesId', 'NamaHakAksesEn', 'ModulId', 'ModulEn', 'KeteranganId', 'KeteranganEn'];

        foreach ($columns as $col) {
            if (Schema::hasColumn('MHakAkses', $col)) {
                DB::unprepared("ALTER TABLE MHakAkses DROP COLUMN [{$col}]");
            }
        }
    }
};
