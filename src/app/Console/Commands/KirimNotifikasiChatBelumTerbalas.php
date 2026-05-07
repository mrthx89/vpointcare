<?php

namespace App\Console\Commands;

use App\Services\Ai\ChatBelumTerbalasNotifier;
use Illuminate\Console\Command;

class KirimNotifikasiChatBelumTerbalas extends Command
{
    protected $signature = 'vpoint:kirim-notifikasi-chat-belum-terbalas';

    protected $description = 'Kirim notifikasi WhatsApp ke user internal untuk chat customer yang belum terbalas.';

    public function handle(ChatBelumTerbalasNotifier $notifier): int
    {
        $result = $notifier->handle();

        if (($result['dilewati_jadwal'] ?? 0) === 1) {
            $this->info('Notifikasi internal dilewati karena sedang di luar jam kerja atau hari libur.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Chat: %d, penerima: %d, terkirim: %d, gagal: %d',
            $result['chat_diperiksa'],
            $result['penerima'],
            $result['notifikasi_terkirim'],
            $result['notifikasi_gagal'],
        ));

        return self::SUCCESS;
    }
}
