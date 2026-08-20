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

        $operasional = DB::table('MHakAkses')
            ->whereNull('KodeHakAkses')
            ->whereNull('IdHakAkses')
            ->where(function ($q) {
                $q->where('NamaHakAksesId', 'Operasional')
                  ->orWhere('NamaHakAkses', 'Operasional')
                  ->orWhere('NamaHakAksesEn', 'Operational');
            })
            ->first();

        if (! $operasional) {
            $operasionalId = (string) Str::orderedUuid();
            DB::table('MHakAkses')->insert([
                'Id' => $operasionalId,
                'IdHakAkses' => null,
                'KodeHakAkses' => null,
                'NamaHakAkses' => 'Operasional',
                'NamaHakAksesId' => 'Operasional',
                'NamaHakAksesEn' => 'Operational',
                'Modul' => 'Operasional',
                'ModulId' => 'Operasional',
                'ModulEn' => 'Operational',
                'Keterangan' => 'Group menu untuk operasional customer service.',
                'KeteranganId' => 'Group menu untuk operasional customer service.',
                'KeteranganEn' => 'Menu group for customer service operations.',
                'SortOrder' => 10,
                'IconString' => 'heroicon-o-chat-bubble-left-right',
                'NonAktif' => false,
                'TglBuat' => now(),
                'TglEdit' => now(),
            ]);
        } else {
            $operasionalId = $operasional->Id;
            DB::table('MHakAkses')->where('Id', $operasionalId)->update([
                'NonAktif' => false,
                'SortOrder' => $operasional->SortOrder ?? 10,
                'IconString' => $operasional->IconString ?? 'heroicon-o-chat-bubble-left-right',
                'TglEdit' => now(),
            ]);
        }

        DB::table('MHakAkses')->where('KodeHakAkses', 'inbox.view')->update([
            'IdHakAkses' => $operasionalId,
            'NamaHakAkses' => 'Inbox WhatsApp',
            'NamaHakAksesId' => 'Inbox WhatsApp',
            'NamaHakAksesEn' => 'WhatsApp Inbox',
            'Modul' => 'Operasional',
            'ModulId' => 'Operasional',
            'ModulEn' => 'Operational',
            'SortOrder' => 10,
            'IconString' => 'heroicon-o-inbox-stack',
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
