<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The hak akses multilingual migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MHakAkses', 'NamaHakAksesId') IS NULL
    ALTER TABLE MHakAkses ADD NamaHakAksesId varchar(150) NULL;
IF COL_LENGTH('MHakAkses', 'NamaHakAksesEn') IS NULL
    ALTER TABLE MHakAkses ADD NamaHakAksesEn varchar(150) NULL;
IF COL_LENGTH('MHakAkses', 'ModulId') IS NULL
    ALTER TABLE MHakAkses ADD ModulId varchar(100) NULL;
IF COL_LENGTH('MHakAkses', 'ModulEn') IS NULL
    ALTER TABLE MHakAkses ADD ModulEn varchar(100) NULL;
IF COL_LENGTH('MHakAkses', 'KeteranganId') IS NULL
    ALTER TABLE MHakAkses ADD KeteranganId varchar(255) NULL;
IF COL_LENGTH('MHakAkses', 'KeteranganEn') IS NULL
    ALTER TABLE MHakAkses ADD KeteranganEn varchar(255) NULL;
SQL);

        foreach (\App\Support\AccessPermissions::localizedDefinitions() as $code => $permission) {
            DB::table('MHakAkses')
                ->where('KodeHakAkses', $code)
                ->update([
                    'NamaHakAkses' => $permission['label_id'],
                    'NamaHakAksesId' => $permission['label_id'],
                    'NamaHakAksesEn' => $permission['label_en'],
                    'Modul' => $permission['module_id'],
                    'ModulId' => $permission['module_id'],
                    'ModulEn' => $permission['module_en'],
                    'Keterangan' => $permission['description_id'],
                    'KeteranganId' => $permission['description_id'],
                    'KeteranganEn' => $permission['description_en'],
                    'TglEdit' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MHakAkses', 'KeteranganEn') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN KeteranganEn;
IF COL_LENGTH('MHakAkses', 'KeteranganId') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN KeteranganId;
IF COL_LENGTH('MHakAkses', 'ModulEn') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN ModulEn;
IF COL_LENGTH('MHakAkses', 'ModulId') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN ModulId;
IF COL_LENGTH('MHakAkses', 'NamaHakAksesEn') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN NamaHakAksesEn;
IF COL_LENGTH('MHakAkses', 'NamaHakAksesId') IS NOT NULL
    ALTER TABLE MHakAkses DROP COLUMN NamaHakAksesId;
SQL);
    }
};
