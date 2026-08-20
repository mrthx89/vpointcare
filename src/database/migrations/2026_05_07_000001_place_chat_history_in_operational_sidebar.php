<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('MHakAkses') || ! Schema::hasColumn('MHakAkses', 'IdHakAkses')) {
            return;
        }

        $operasionalParent = DB::table('MHakAkses as child')
            ->join('MHakAkses as parent', 'parent.Id', '=', 'child.IdHakAkses')
            ->where('child.KodeHakAkses', 'inbox.view')
            ->select('parent.Id')
            ->first();

        $operasionalId = $operasionalParent ? $operasionalParent->Id : null;

        if (! $operasionalId) {
            $parent = DB::table('MHakAkses')
                ->whereNull('KodeHakAkses')
                ->whereNull('IdHakAkses')
                ->where(function ($q) {
                    $q->where('NamaHakAksesId', 'Operasional')
                      ->orWhere('NamaHakAkses', 'Operasional')
                      ->orWhere('NamaHakAksesEn', 'Operational');
                })
                ->orderBy('SortOrder')
                ->first();
            $operasionalId = $parent ? $parent->Id : null;
        }

        if ($operasionalId) {
            DB::table('MHakAkses')->where('KodeHakAkses', 'chat_history.view')->update([
                'IdHakAkses' => $operasionalId,
                'NamaHakAkses' => 'Histori Chat',
                'NamaHakAksesId' => 'Histori Chat',
                'NamaHakAksesEn' => 'Chat History',
                'Modul' => 'Operasional',
                'ModulId' => 'Operasional',
                'ModulEn' => 'Operational',
                'Keterangan' => 'Melihat daftar histori sesi chat dan membuka detail percakapan.',
                'KeteranganId' => 'Melihat daftar histori sesi chat dan membuka detail percakapan.',
                'KeteranganEn' => 'View chat session history list and open conversation details.',
                'SortOrder' => 11,
                'IconString' => 'heroicon-o-clock',
                'NonAktif' => false,
                'TglEdit' => now(),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
