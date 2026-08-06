<?php

namespace Tests\Unit\Services\Waha;

use App\Services\Waha\WahaSender;
use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class WahaWebhookProcessorTest extends TestCase
{
    public function test_group_jid_is_prioritized_over_an_earlier_participant_chat_id(): void
    {
        $processor = new WahaWebhookProcessor(new WahaSender);
        $parseMessage = new ReflectionMethod($processor, 'parseMessage');

        $parsed = $parseMessage->invoke($processor, [], [
            'chatId' => '628123456789@c.us',
            'participant' => '628123456789@c.us',
            '_data' => [
                'Info' => [
                    'Chat' => '120363777777777777@g.us',
                ],
            ],
            'id' => [
                '_serialized' => 'message-group-priority-1',
            ],
        ]);

        self::assertSame('Grup', $parsed['jenis_chat']);
        self::assertSame('120363777777777777@g.us', $parsed['group_jid']);
        self::assertSame('628123456789@c.us', $parsed['pengirim_jid']);
    }

    public function test_group_chat_lookup_is_limited_to_the_incoming_whatsapp_session(): void
    {
        $this->createFindOrCreateChatTables();

        try {
            DB::table('MStatusChat')->insert([
                ['Id' => 'status-open', 'KodeStatusChat' => 'MENUNGGU_CS', 'NamaStatusChat' => 'Menunggu CS'],
                ['Id' => 'status-closed', 'KodeStatusChat' => 'DITUTUP', 'NamaStatusChat' => 'Ditutup'],
            ]);

            $groupJid = '120363777777777777@g.us';
            $now = now();

            DB::table('TChat')->insert([
                [
                    'Id' => 'group-session-a',
                    'IdSesiWhatsapp' => 'session-a',
                    'IdStatusChat' => 'status-open',
                    'JenisChat' => 'Grup',
                    'NomorWhatsapp' => $groupJid,
                    'IdWahaTerdeteksi' => $groupJid,
                    'TglChatTerakhir' => $now->copy()->subMinute(),
                ],
                [
                    'Id' => 'group-session-b',
                    'IdSesiWhatsapp' => 'session-b',
                    'IdStatusChat' => 'status-open',
                    'JenisChat' => 'Grup',
                    'NomorWhatsapp' => $groupJid,
                    'IdWahaTerdeteksi' => $groupJid,
                    'TglChatTerakhir' => $now,
                ],
            ]);

            self::assertSame(
                'group-session-b',
                DB::table('TChat')->orderByDesc('TglChatTerakhir')->value('Id')
            );

            $processor = new WahaWebhookProcessor(new WahaSender);
            $findOrCreateChat = new ReflectionMethod($processor, 'findOrCreateChat');

            $chatId = $findOrCreateChat->invoke($processor, 'session-a', [
                'jenis_chat' => 'Grup',
                'group_jid' => $groupJid,
                'pengirim_nomor' => null,
                'pengirim_jid' => '628123456789@c.us',
                'tgl_pesan' => $now,
            ], $this->emptyChatMapping());

            self::assertSame('group-session-a', $chatId);
        } finally {
            Schema::dropIfExists('TChat');
            Schema::dropIfExists('MStatusChat');
            Cache::flush();
        }
    }

    private function createFindOrCreateChatTables(): void
    {
        Cache::flush();

        Schema::create('MStatusChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeStatusChat');
            $table->string('NamaStatusChat');
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdSesiWhatsapp')->nullable();
            $table->string('IdStatusChat')->nullable();
            $table->string('IdCustomer')->nullable();
            $table->string('IdInstansi')->nullable();
            $table->string('IdNomorWhatsapp')->nullable();
            $table->string('IdGrupWhatsapp')->nullable();
            $table->string('JenisChat');
            $table->string('NomorWhatsapp');
            $table->string('NamaKontak')->nullable();
            $table->string('NamaGrupWhatsapp')->nullable();
            $table->string('IdWahaTerdeteksi')->nullable();
            $table->dateTime('TglChatTerakhir')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });
    }

    /** @return array<string, null> */
    private function emptyChatMapping(): array
    {
        return [
            'IdCustomer' => null,
            'IdInstansi' => null,
            'IdNomorWhatsapp' => null,
            'IdGrupWhatsapp' => null,
            'NamaKontak' => null,
            'NamaGrupWhatsapp' => null,
        ];
    }
}
