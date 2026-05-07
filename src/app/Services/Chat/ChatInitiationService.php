<?php

namespace App\Services\Chat;

use App\Events\WahaInboxUpdated;
use App\Services\Waha\WahaSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatInitiationService
{
    public function __construct(private readonly WahaSender $wahaSender)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function start(array $data, ?string $penggunaId): array
    {
        $target = $this->resolveTarget($data);

        if ($this->isExcludedNumber($target['NomorWhatsapp'])) {
            throw ValidationException::withMessages([
                'startChatManualNumber' => __('ui.pages.inbox.start_chat_number_excluded'),
            ]);
        }

        $chat = $this->findActiveChat($target['NomorWhatsapp'], $target['IdNomorWhatsapp']);
        $existing = (bool) $chat;

        if (! $chat) {
            $chat = $this->createChat($target, $this->resolveSessionId((string) ($data['session_id'] ?? '')), $penggunaId);
        }

        $sessionCode = (string) (DB::table('MSesiWhatsapp')->where('Id', $chat->IdSesiWhatsapp)->value('KodeSesi') ?: 'default');
        $message = trim((string) ($data['message'] ?? ''));
        $deliveryMode = (string) ($data['delivery_mode'] ?? 'send');
        $sendNow = $deliveryMode === 'send';
        $response = ['ok' => true];

        if ($sendNow) {
            $response = $this->wahaSender->sendText(
                $sessionCode,
                $target['NomorWhatsapp'],
                $message,
                'WAHA_START_CHAT_TEXT'
            );
        }

        $success = (bool) ($response['ok'] ?? false);
        $status = $sendNow
            ? ($success ? 'Terkirim WAHA' : 'Gagal WAHA')
            : 'Draft Lokal';

        $messageId = (string) Str::orderedUuid();

        DB::table('TChatD')->insert([
            'Id' => $messageId,
            'IdChat' => $chat->Id,
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $message,
            'DikirimOlehCustomer' => false,
            'TglPesan' => now(),
            'TglDikirim' => $sendNow && $success ? now() : null,
            'StatusKirim' => $status,
            'PesanError' => $sendNow && ! $success ? ($response['error'] ?? __('ui.pages.inbox.waha_send_failed')) : null,
            'DibalasOleh' => $penggunaId,
            'TglBuat' => now(),
        ]);

        DB::table('TChat')->where('Id', $chat->Id)->update([
            'TglDibalasTerakhir' => now(),
            'TglChatTerakhir' => now(),
            'JumlahPesanBelumDibaca' => 0,
            'TglEdit' => now(),
        ]);

        event(new WahaInboxUpdated((string) $chat->Id));

        return [
            'chat_id' => (string) $chat->Id,
            'message_id' => $messageId,
            'was_existing_chat' => $existing,
            'sent_to_waha' => $sendNow,
            'send_success' => ! $sendNow || $success,
            'send_error' => $sendNow && ! $success ? ($response['error'] ?? null) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveTarget(array $data): array
    {
        $nomorWhatsappId = trim((string) ($data['nomor_whatsapp_id'] ?? ''));

        if ($nomorWhatsappId !== '') {
            $nomor = DB::table('MNomorWhatsapp')
                ->where('Id', $nomorWhatsappId)
                ->where('NonAktif', false)
                ->first();

            if (! $nomor) {
                throw ValidationException::withMessages([
                    'startChatNomorWhatsappId' => __('ui.pages.inbox.start_chat_contact_not_found'),
                ]);
            }

            $number = $this->normalizePhoneNumber((string) $nomor->NomorWhatsapp);

            return [
                'NomorWhatsapp' => $number,
                'IdNomorWhatsapp' => $nomor->Id,
                'IdCustomer' => $nomor->IdCustomer ?? null,
                'IdInstansi' => $nomor->IdInstansi ?? null,
                'NamaKontak' => $nomor->NamaKontak ?? null,
                'IdWaha' => Schema::hasColumn('MNomorWhatsapp', 'IdWaha') ? ($nomor->IdWaha ?? null) : null,
            ];
        }

        $number = $this->normalizePhoneNumber((string) ($data['manual_number'] ?? ''));

        if ($number === '' || strlen($number) < 10) {
            throw ValidationException::withMessages([
                'startChatManualNumber' => __('ui.pages.inbox.start_chat_invalid_number'),
            ]);
        }

        return [
            'NomorWhatsapp' => $number,
            'IdNomorWhatsapp' => null,
            'IdCustomer' => null,
            'IdInstansi' => null,
            'NamaKontak' => trim((string) ($data['manual_name'] ?? '')) ?: null,
            'IdWaha' => null,
        ];
    }

    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number) ?: '';

        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62'.$number;
        }

        return $number;
    }

    private function isExcludedNumber(string $number): bool
    {
        if (! Schema::hasColumn('MPengaturanAi', 'ExcludeNomorWhatsapp')) {
            return false;
        }

        $settings = DB::table('MPengaturanAi')
            ->where('KodePengaturan', 'DEFAULT')
            ->where('NonAktif', false)
            ->value('ExcludeNomorWhatsapp');

        if (! is_string($settings) || trim($settings) === '') {
            return false;
        }

        foreach (preg_split('/[\s,;]+/', $settings) ?: [] as $excluded) {
            if ($this->normalizePhoneNumber((string) $excluded) === $number) {
                return true;
            }
        }

        return false;
    }

    private function findActiveChat(string $number, ?string $nomorWhatsappId): ?object
    {
        $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');

        $query = DB::table('TChat')
            ->where('JenisChat', 'Pribadi')
            ->where(function ($query) use ($number, $nomorWhatsappId): void {
                $query->where('NomorWhatsapp', $number);

                if ($nomorWhatsappId) {
                    $query->orWhere('IdNomorWhatsapp', $nomorWhatsappId);
                }

                if (Schema::hasColumn('TChat', 'NomorWhatsappTerdeteksi')) {
                    $query->orWhere('NomorWhatsappTerdeteksi', $number);
                }
            });

        if ($statusDitutupId) {
            $query->where(function ($query) use ($statusDitutupId): void {
                $query->where('IdStatusChat', '!=', $statusDitutupId)
                    ->orWhereNull('IdStatusChat');
            });
        }

        return $query->orderByDesc('TglChatTerakhir')->first();
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function createChat(array $target, string $sessionId, ?string $penggunaId): object
    {
        $id = (string) Str::orderedUuid();
        $chat = [
            'Id' => $id,
            'IdSesiWhatsapp' => $sessionId,
            'IdStatusChat' => DB::table('MStatusChat')->where('KodeStatusChat', 'MENUNGGU_CS')->value('Id'),
            'IdCustomer' => $target['IdCustomer'],
            'IdInstansi' => $target['IdInstansi'],
            'IdNomorWhatsapp' => $target['IdNomorWhatsapp'],
            'JenisChat' => 'Pribadi',
            'NomorWhatsapp' => $target['NomorWhatsapp'],
            'NamaKontak' => $target['NamaKontak'],
            'Prioritas' => 'Normal',
            'TglChatTerakhir' => now(),
            'TglDibalasTerakhir' => now(),
            'JumlahPesanBelumDibaca' => 0,
            'TglBuat' => now(),
        ];

        if (Schema::hasColumn('TChat', 'DiambilOleh')) {
            $chat['DiambilOleh'] = $penggunaId;
        }

        if (Schema::hasColumn('TChat', 'IdWahaTerdeteksi')) {
            $chat['IdWahaTerdeteksi'] = $target['IdWaha'] ?: $target['NomorWhatsapp'].'@c.us';
        }

        if (Schema::hasColumn('TChat', 'NomorWhatsappTerdeteksi')) {
            $chat['NomorWhatsappTerdeteksi'] = $target['NomorWhatsapp'];
        }

        DB::table('TChat')->insert($chat);

        return DB::table('TChat')->where('Id', $id)->first();
    }

    private function resolveSessionId(string $sessionId): string
    {
        if ($sessionId !== '') {
            $existing = DB::table('MSesiWhatsapp')
                ->where('Id', $sessionId)
                ->where('NonAktif', false)
                ->first();

            if ($existing) {
                return (string) $existing->Id;
            }
        }

        $default = DB::table('MSesiWhatsapp')
            ->where('NonAktif', false)
            ->orderByRaw("CASE WHEN KodeSesi = 'default' THEN 0 ELSE 1 END")
            ->orderBy('KodeSesi')
            ->first();

        if ($default) {
            return (string) $default->Id;
        }

        $id = (string) Str::orderedUuid();

        DB::table('MSesiWhatsapp')->insert([
            'Id' => $id,
            'KodeSesi' => 'default',
            'NamaSesi' => 'default',
            'BaseUrlWaha' => config('services.waha.base_url', '-'),
            'StatusSesi' => 'Aktif',
            'NonAktif' => false,
            'TglBuat' => now(),
        ]);

        return $id;
    }
}
