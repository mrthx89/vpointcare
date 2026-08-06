<?php

namespace App\Console\Commands;

use App\Support\WahaChatHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillGroupChatIdentity extends Command
{
    protected $signature = 'waha:backfill-group-chat-identity {--dry-run}';

    protected $description = 'Isi canonical group JID legacy pada TChat tanpa mengubah pesan atau merge room.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['candidate' => 0, 'updated' => 0, 'skipped' => 0, 'unparseable' => 0];

        DB::table('TChat')
            ->where('JenisChat', 'Grup')
            ->where(function ($query): void {
                $query->whereNull('IdWahaTerdeteksi')->orWhere('IdWahaTerdeteksi', '');
            })
            ->orderBy('Id')
            ->chunkById(500, function ($rows) use (&$counts, $dryRun): void {
                foreach ($rows as $row) {
                    $detail = DB::table('TChatD')
                        ->where('IdChat', $row->Id)
                        ->whereNotNull('PayloadJson')
                        ->orderByDesc('TglPesan')
                        ->orderByDesc('Id')
                        ->select('PayloadJson')
                        ->first();

                    $payload = $detail ? json_decode((string) $detail->PayloadJson, true) : null;
                    $groupJid = is_array($payload) ? WahaChatHelper::groupJidFromPayload($payload) : null;

                    if (! $groupJid) {
                        $counts['unparseable']++;

                        continue;
                    }

                    $counts['candidate']++;
                    if ($dryRun) {
                        $counts['updated']++;

                        continue;
                    }

                    $updated = DB::table('TChat')
                        ->where('Id', $row->Id)
                        ->where(function ($query): void {
                            $query->whereNull('IdWahaTerdeteksi')->orWhere('IdWahaTerdeteksi', '');
                        })
                        ->update([
                            'IdWahaTerdeteksi' => $groupJid,
                            'TglEdit' => now(),
                        ]);

                    if ($updated > 0) {
                        $counts['updated']++;
                    } else {
                        $counts['skipped']++;
                    }
                }
            }, 'Id', 'Id');

        Log::info('waha.group_identity_backfill.completed', [
            'dry_run' => $dryRun,
            ...$counts,
        ]);

        $this->table(['candidate', 'updated', 'skipped', 'unparseable'], [array_values($counts)]);

        return self::SUCCESS;
    }
}
