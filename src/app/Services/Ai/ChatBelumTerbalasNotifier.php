<?php

namespace App\Services\Ai;

use App\Services\Waha\WahaSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatBelumTerbalasNotifier
{
    public function __construct(private readonly WahaSender $wahaSender)
    {
    }

    /**
     * @return array<string, int>
     */
    public function handle(): array
    {
        $settings = DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->where('NonAktif', false)
            ->first();

        if (! $settings || ! (bool) ($settings->NotifikasiChatBelumTerbalasAktif ?? false)) {
            return [
                'chat_diperiksa' => 0,
                'notifikasi_terkirim' => 0,
                'notifikasi_gagal' => 0,
                'penerima' => 0,
            ];
        }

        $recipients = $this->recipients((string) ($settings->KodePeranPenerimaNotifikasi ?? ''));

        if ($recipients->isEmpty()) {
            return [
                'chat_diperiksa' => 0,
                'notifikasi_terkirim' => 0,
                'notifikasi_gagal' => 0,
                'penerima' => 0,
            ];
        }

        $waitMinutes = max(1, (int) ($settings->MenitTungguNotifikasi ?? 10));
        $cooldownMinutes = max(1, (int) ($settings->JedaNotifikasiMenit ?? 30));
        $chatRows = $this->unansweredChats($waitMinutes, $cooldownMinutes);
        $sent = 0;
        $failed = 0;
        $session = (string) config('services.waha.notification_session', 'default');

        foreach ($chatRows as $chat) {
            foreach ($recipients as $recipient) {
                $message = $this->message($settings, $chat, $recipient, $waitMinutes);
                $result = $this->wahaSender->sendText(
                    $session,
                    (string) $recipient->NomorWhatsappInternal,
                    $message,
                    'WAHA_NOTIF_CHAT_BELUM_DIBALAS'
                );

                $result['ok'] ? $sent++ : $failed++;
            }

            DB::table('TChatM')->where('Id', $chat->Id)->update([
                'TglNotifikasiBelumTerbalasTerakhir' => now(),
                'JumlahNotifikasiBelumTerbalas' => DB::raw('JumlahNotifikasiBelumTerbalas + 1'),
                'TglEdit' => now(),
            ]);
        }

        return [
            'chat_diperiksa' => $chatRows->count(),
            'notifikasi_terkirim' => $sent,
            'notifikasi_gagal' => $failed,
            'penerima' => $recipients->count(),
        ];
    }

    private function recipients(string $roleCodes)
    {
        $codes = array_values(array_filter(array_map(
            fn (string $value): string => trim($value),
            explode(',', $roleCodes)
        )));

        return DB::table('MPengguna as p')
            ->leftJoin('MPeran as r', 'r.Id', '=', 'p.IdPeran')
            ->where('p.NonAktif', false)
            ->whereNotNull('p.NomorWhatsappInternal')
            ->where('p.NomorWhatsappInternal', '<>', '')
            ->when($codes !== [], fn ($query) => $query->whereIn('r.KodePeran', $codes))
            ->select('p.Id', 'p.NamaPengguna', 'p.NomorWhatsappInternal', 'r.KodePeran')
            ->get();
    }

    private function unansweredChats(int $waitMinutes, int $cooldownMinutes)
    {
        $latestIncoming = DB::table('TChatD')
            ->select('IdChatM', DB::raw('MAX(TglPesan) as TglPesanTerakhirMasuk'))
            ->where('ArahPesan', 'Masuk')
            ->where('DikirimOlehCustomer', true)
            ->groupBy('IdChatM');

        $latestCsReply = DB::table('TChatD')
            ->select('IdChatM', DB::raw('MAX(TglPesan) as TglPesanTerakhirCs'))
            ->where('ArahPesan', 'Keluar')
            ->where(function ($query): void {
                $query->whereNull('DihasilkanOlehAi')
                    ->orWhere('DihasilkanOlehAi', false);
            })
            ->groupBy('IdChatM');

        return DB::table('TChatM as c')
            ->joinSub($latestIncoming, 'masuk', fn ($join) => $join->on('masuk.IdChatM', '=', 'c.Id'))
            ->leftJoinSub($latestCsReply, 'cs', fn ($join) => $join->on('cs.IdChatM', '=', 'c.Id'))
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->leftJoin('MCustomer as m', 'm.Id', '=', 'c.IdCustomer')
            ->where(function ($query): void {
                $query->whereNull('cs.TglPesanTerakhirCs')
                    ->orWhereColumn('cs.TglPesanTerakhirCs', '<', 'masuk.TglPesanTerakhirMasuk');
            })
            ->where('masuk.TglPesanTerakhirMasuk', '<=', now()->subMinutes($waitMinutes))
            ->where(function ($query) use ($cooldownMinutes): void {
                $query->whereNull('c.TglNotifikasiBelumTerbalasTerakhir')
                    ->orWhere('c.TglNotifikasiBelumTerbalasTerakhir', '<=', now()->subMinutes($cooldownMinutes));
            })
            ->orderBy('masuk.TglPesanTerakhirMasuk')
            ->limit(20)
            ->select(
                'c.Id',
                'c.JenisChat',
                'c.NomorWhatsapp',
                'c.NamaKontak',
                'c.NamaGrupWhatsapp',
                'i.NamaInstansi',
                'm.NamaCustomer',
                'masuk.TglPesanTerakhirMasuk'
            )
            ->get()
            ->map(function (object $chat): object {
                $chat->PesanTerakhir = (string) DB::table('TChatD')
                    ->where('IdChatM', $chat->Id)
                    ->where('ArahPesan', 'Masuk')
                    ->where('TglPesan', $chat->TglPesanTerakhirMasuk)
                    ->value('IsiPesan');

                return $chat;
            });
    }

    private function message(object $settings, object $chat, object $recipient, int $waitMinutes): string
    {
        $template = $settings->TemplateNotifikasiChatBelumTerbalas ?: $this->defaultTemplate();
        $adminUrl = rtrim((string) config('app.url'), '/') . '/admin/inbox-whatsapp';
        $lastIncomingAt = Carbon::parse($chat->TglPesanTerakhirMasuk);
        $minutes = max($waitMinutes, $lastIncomingAt->diffInMinutes(now()));

        return strtr($template, [
            '{nama_user}' => (string) $recipient->NamaPengguna,
            '{nama_instansi}' => $chat->NamaInstansi ?: $chat->NamaCustomer ?: 'Belum dipetakan',
            '{jenis_chat}' => (string) $chat->JenisChat,
            '{nama_kontak}' => $chat->JenisChat === 'Grup'
                ? ($chat->NamaGrupWhatsapp ?: 'Grup WhatsApp')
                : ($chat->NamaKontak ?: 'Customer'),
            '{nomor_whatsapp}' => (string) $chat->NomorWhatsapp,
            '{pesan_terakhir}' => Str::limit((string) $chat->PesanTerakhir, 180),
            '{menit_menunggu}' => (string) $minutes,
            '{url_admin}' => $adminUrl,
        ]);
    }

    private function defaultTemplate(): string
    {
        return 'Halo {nama_user}, ada chat WhatsApp dari {nama_instansi} yang belum dibalas selama {menit_menunggu} menit. Kontak: {nama_kontak} ({nomor_whatsapp}). Pesan terakhir: {pesan_terakhir}. Silakan cek VPoint Care: {url_admin}';
    }
}
