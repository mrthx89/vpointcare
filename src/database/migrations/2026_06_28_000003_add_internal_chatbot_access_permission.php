<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $existingChatbot = DB::table('MHakAkses')->where('KodeHakAkses', 'chatbot.access')->first();

        if (! $existingChatbot) {
            DB::table('MHakAkses')->insert([
                'Id' => (string) Str::orderedUuid(),
                'IdHakAkses' => $operasionalId,
                'KodeHakAkses' => 'chatbot.access',
                'NamaHakAkses' => 'CareDesk Assistant',
                'NamaHakAksesId' => 'CareDesk Assistant',
                'NamaHakAksesEn' => 'CareDesk Assistant',
                'Modul' => 'Operasional',
                'ModulId' => 'Operasional',
                'ModulEn' => 'Operational',
                'Keterangan' => 'Mengakses chatbot internal untuk bantuan operasional CareDesk.',
                'KeteranganId' => 'Mengakses chatbot internal untuk bantuan operasional CareDesk.',
                'KeteranganEn' => 'Access the internal chatbot for CareDesk operational assistance.',
                'SortOrder' => 15,
                'IconString' => 'heroicon-o-chat-bubble-bottom-center-text',
                'NonAktif' => false,
                'TglBuat' => now(),
                'TglEdit' => now(),
            ]);
        } else {
            DB::table('MHakAkses')->where('KodeHakAkses', 'chatbot.access')->update([
                'IdHakAkses' => $operasionalId,
                'NamaHakAkses' => 'CareDesk Assistant',
                'NamaHakAksesId' => 'CareDesk Assistant',
                'NamaHakAksesEn' => 'CareDesk Assistant',
                'Modul' => 'Operasional',
                'ModulId' => 'Operasional',
                'ModulEn' => 'Operational',
                'Keterangan' => 'Mengakses chatbot internal untuk bantuan operasional CareDesk.',
                'KeteranganId' => 'Mengakses chatbot internal untuk bantuan operasional CareDesk.',
                'KeteranganEn' => 'Access the internal chatbot for CareDesk operational assistance.',
                'SortOrder' => 15,
                'IconString' => 'heroicon-o-chat-bubble-bottom-center-text',
                'NonAktif' => false,
                'TglEdit' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MHakAkses')) {
            DB::table('MHakAkses')->where('KodeHakAkses', 'chatbot.access')->delete();
        }
    }
};
