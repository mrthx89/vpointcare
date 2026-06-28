<?php

namespace App\Support;

use App\Services\Waha\WahaSender;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WahaChatHelper
{
    public static function normalizeChatId(string $chatIdOrNumber): string
    {
        $chatIdOrNumber = trim($chatIdOrNumber);

        if (str_contains($chatIdOrNumber, '@')) {
            return str_ends_with($chatIdOrNumber, '@s.whatsapp.net')
                ? str_replace('@s.whatsapp.net', '@c.us', $chatIdOrNumber)
                : $chatIdOrNumber;
        }

        $number = preg_replace('/[^0-9]/', '', $chatIdOrNumber) ?: $chatIdOrNumber;

        return $number.'@c.us';
    }

    public static function normalizeContactId(string $contactId): string
    {
        return self::normalizeChatId(trim($contactId));
    }

    public static function normalizePhoneNumber(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        if (str_contains($number, '@lid')) {
            return null;
        }

        $number = preg_replace('/@.+$/', '', $number) ?: $number;
        $number = preg_replace('/:.+$/', '', $number) ?: $number;

        return preg_replace('/[^0-9]/', '', $number) ?: null;
    }

    public static function latestIncomingWahaChatId(string $chatId): ?string
    {
        $payloadJson = DB::table('TChatD')
            ->where('IdChat', $chatId)
            ->where('ArahPesan', 'Masuk')
            ->whereNotNull('PayloadJson')
            ->orderByDesc('TglPesan')
            ->value('PayloadJson');

        if (! $payloadJson) {
            return null;
        }

        $payload = json_decode((string) $payloadJson, true);

        if (! is_array($payload)) {
            return null;
        }

        foreach ([
            'chatId',
            'from',
            'from.id',
            '_data.id.remote',
            '_data.Info.Chat',
            'key.remoteJid',
        ] as $key) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && $value !== '') {
                return self::normalizeChatId($value);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $parsed @return array<string, mixed> */
    public static function resolveLidPhoneNumber(WahaSender $wahaSender, string $session, array $parsed): array
    {
        $jid = (string) ($parsed['pengirim_jid'] ?? '');

        if (! str_contains($jid, '@lid')) {
            return $parsed;
        }

        $result = $wahaSender->getPhoneNumberByLid($session ?: 'default', $jid);
        $phone = $result['phone'] ?? null;

        if (is_string($phone) && $phone !== '') {
            $parsed['pengirim_nomor'] = $phone;
            $parsed['pengirim_phone_jid'] = ($result['pn'] ?? null) ?: $phone.'@c.us';
        }

        return $parsed;
    }
}
