<?php

namespace App\Filament\Concerns;

use App\Support\LocaleFormatter;
use Illuminate\Support\Facades\DB;

/**
 * Trait untuk me-resolve nama pembuat catatan internal chat.
 *
 * Dipakai oleh InboxWhatsapp dan ViewChatSession agar logika tidak terduplikasi
 * dan query dibuat optimal (satu query ke MPengguna untuk seluruh baris).
 */
trait ResolvesCatatanInternal
{
    /**
     * Ambil catatan internal untuk satu chat dan resolve nama pembuatnya.
     *
     * @param  string  $chatId  ID dari TChat
     * @return array<int, array{Id: string, IsiCatatan: string, TglBuat: string, TglFormatted: string, DibuatOlehNama: string, NamaPembuat: string}>
     */
    protected function catatanInternalRows(string $chatId): array
    {
        $rows = DB::table('TChatDCatatanInternal')
            ->where('IdChat', $chatId)
            ->orderBy('TglBuat', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Kumpulkan seluruh DibuatOleh yang tidak kosong untuk lookup bulk
        $penggunaIds = $rows->pluck('DibuatOleh')->filter()->unique()->values()->all();

        // Satu query untuk mapping Id => NamaPengguna
        // Tidak memfilter NonAktif agar nama pengguna nonaktif tetap tampil pada catatan historis
        $penggunaMap = DB::table('MPengguna')
            ->whereIn('Id', $penggunaIds)
            ->pluck('NamaPengguna', 'Id')
            ->all();

        return $rows->map(function ($row) use ($penggunaMap) {
            // Resolve nama: prioritas dari map, fallback ke system
            $namaPembuat = __('ui.common.system');
            if ($row->DibuatOleh && isset($penggunaMap[$row->DibuatOleh])) {
                $namaPembuat = $penggunaMap[$row->DibuatOleh] ?: __('ui.common.system');
            }

            return [
                'Id' => $row->Id,
                'IsiCatatan' => $row->IsiCatatan,
                'TglBuat' => $row->TglBuat,
                'TglFormatted' => LocaleFormatter::dateTime($row->TglBuat),
                'DibuatOlehNama' => $namaPembuat, // untuk InboxWhatsapp
                'NamaPembuat' => $namaPembuat,    // untuk ViewChatSession
            ];
        })->all();
    }
}
