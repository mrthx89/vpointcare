<?php

namespace Tests\Feature\Waha;

use App\Services\Waha\WahaSender;
use App\Services\Waha\WahaWebhookProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WahaWebhookGroupIngestionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi');
            $table->string('NamaSesi')->nullable();
            $table->string('BaseUrlWaha')->nullable();
            $table->string('StatusSesi')->nullable();
            $table->boolean('NonAktif')->default(false);
            $table->dateTime('TglBuat')->nullable();
        });

        Schema::create('MStatusChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeStatusChat');
        });

        Schema::create('MNomorWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdCustomer')->nullable();
            $table->string('IdInstansi')->nullable();
            $table->string('NomorWhatsapp');
            $table->string('NamaKontak')->nullable();
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('MGrupWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdInstansi')->nullable();
            $table->string('NamaGrup')->nullable();
            $table->string('IdGrupWaha')->nullable();
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('TLogWebhookWaha', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdSesiWhatsapp')->nullable();
            $table->string('JenisEvent')->nullable();
            $table->text('PayloadJson')->nullable();
            $table->dateTime('TglDiterima')->nullable();
            $table->boolean('SudahDiproses')->default(false);
            $table->dateTime('TglDiproses')->nullable();
            $table->dateTime('TglBuat')->nullable();
            $table->dateTime('TglEdit')->nullable();
            $table->text('PesanError')->nullable();
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdSesiWhatsapp');
            $table->string('IdStatusChat')->nullable();
            $table->string('IdCustomer')->nullable();
            $table->string('IdInstansi')->nullable();
            $table->string('IdNomorWhatsapp')->nullable();
            $table->string('IdGrupWhatsapp')->nullable();
            $table->string('JenisChat');
            $table->string('NomorWhatsapp');
            $table->string('NamaKontak')->nullable();
            $table->string('NamaGrupWhatsapp')->nullable();
            $table->string('IdWahaTerdeteksi', 200)->nullable();
            $table->string('NomorWhatsappTerdeteksi')->nullable();
            $table->string('Prioritas')->nullable();
            $table->dateTime('TglChatTerakhir')->nullable();
            $table->integer('JumlahPesanBelumDibaca')->default(0);
            $table->dateTime('TglBuat')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->string('IdLogWebhookWaha')->nullable();
            $table->string('IdPesanWaha')->nullable();
            $table->string('ArahPesan');
            $table->string('JenisPesan');
            $table->text('IsiPesan')->nullable();
            $table->string('UrlMedia')->nullable();
            $table->text('PayloadJson')->nullable();
            $table->string('PengirimNomorWhatsapp')->nullable();
            $table->string('PengirimNamaKontak')->nullable();
            $table->string('PengirimIdWaha', 200)->nullable();
            $table->boolean('DikirimOlehCustomer')->default(false);
            $table->dateTime('TglPesan')->nullable();
            $table->string('StatusKirim')->nullable();
            $table->dateTime('TglBuat')->nullable();
        });

        DB::table('MStatusChat')->insert([
            ['Id' => 'status-waiting', 'KodeStatusChat' => 'MENUNGGU_CS'],
            ['Id' => 'status-closed', 'KodeStatusChat' => 'DITUTUP'],
        ]);
        DB::table('MSesiWhatsapp')->insert([
            'Id' => 'session-default',
            'KodeSesi' => 'default',
            'NamaSesi' => 'Default',
            'StatusSesi' => 'Aktif',
            'NonAktif' => false,
            'TglBuat' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('TLogWebhookWaha');
        Schema::dropIfExists('MGrupWhatsapp');
        Schema::dropIfExists('MNomorWhatsapp');
        Schema::dropIfExists('MStatusChat');
        Schema::dropIfExists('MSesiWhatsapp');
        Cache::flush();

        parent::tearDown();
    }

    public function test_group_messages_from_different_participants_share_one_raw_group_chat(): void
    {
        $processor = app(WahaWebhookProcessor::class, [
            'wahaSender' => app(WahaSender::class),
        ]);
        $groupJid = '120363999999999999@g.us';

        $first = $processor->process($this->groupPayload('message-1', $groupJid, '628111111111@c.us', 'Participant One'));
        $second = $processor->process($this->groupPayload('message-2', $groupJid, '628222222222@c.us', 'Participant Two'));

        self::assertTrue($first['ok']);
        self::assertTrue($second['ok']);
        self::assertSame($first['chat_id'], $second['chat_id']);
        self::assertSame(1, DB::table('TChat')->where('JenisChat', 'Grup')->count());
        self::assertSame(2, DB::table('TChatD')->where('IdChat', $first['chat_id'])->count());

        self::assertDatabaseHas('TChat', [
            'Id' => $first['chat_id'],
            'IdSesiWhatsapp' => DB::table('MSesiWhatsapp')->where('KodeSesi', 'default')->value('Id'),
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => $groupJid,
            'IdWahaTerdeteksi' => $groupJid,
        ]);

        $senders = DB::table('TChatD')
            ->where('IdChat', $first['chat_id'])
            ->orderBy('IdPesanWaha')
            ->pluck('PengirimIdWaha')
            ->all();

        self::assertSame(['628111111111@c.us', '628222222222@c.us'], $senders);
        self::assertSame(
            ['628111111111', '628222222222'],
            DB::table('TChatD')->where('IdChat', $first['chat_id'])->orderBy('IdPesanWaha')->pluck('PengirimNomorWhatsapp')->all(),
        );
        self::assertSame(
            ['Participant One', 'Participant Two'],
            DB::table('TChatD')->where('IdChat', $first['chat_id'])->orderBy('IdPesanWaha')->pluck('PengirimNamaKontak')->all(),
        );
        self::assertNull(DB::table('TChat')->where('Id', $first['chat_id'])->value('IdNomorWhatsapp'));
        self::assertNull(DB::table('TChat')->where('Id', $first['chat_id'])->value('NamaKontak'));
    }



    public function test_mapped_group_with_stale_existing_chat_still_uses_raw_group_jid(): void
    {
        DB::table('MGrupWhatsapp')->insert([
            'Id' => 'mapped-group',
            'IdInstansi' => 'instansi-group',
            'NamaGrup' => 'Mapped Group',
            'IdGrupWaha' => '120363999999999999@g.us',
            'NonAktif' => false,
        ]);

        DB::table('TChat')->insert([
            'Id' => 'stale-chat',
            'IdSesiWhatsapp' => 'session-default',
            'IdStatusChat' => 'status-waiting',
            'IdGrupWhatsapp' => 'mapped-group',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => 'stale-participant@c.us',
            'NamaGrupWhatsapp' => 'Mapped Group',
            'IdWahaTerdeteksi' => 'stale-participant@c.us',
            'Prioritas' => 'Normal',
            'TglChatTerakhir' => now()->subMinute(),
            'JumlahPesanBelumDibaca' => 0,
            'TglBuat' => now()->subMinute(),
        ]);

        $processor = app(WahaWebhookProcessor::class, [
            'wahaSender' => app(WahaSender::class),
        ]);
        $groupJid = '120363999999999999@g.us';

        $first = $processor->process($this->groupPayload('message-mapped-1', $groupJid, '628111111111@c.us', 'Participant One'));
        $second = $processor->process($this->groupPayload('message-mapped-2', $groupJid, '628222222222@c.us', 'Participant Two'));

        self::assertTrue($first['ok']);
        self::assertTrue($second['ok']);
        self::assertSame('stale-chat', $first['chat_id']);
        self::assertSame($first['chat_id'], $second['chat_id']);

        self::assertDatabaseHas('TChat', [
            'Id' => 'stale-chat',
            'IdSesiWhatsapp' => 'session-default',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => $groupJid,
            'IdWahaTerdeteksi' => $groupJid,
        ]);

        self::assertSame(
            ['628111111111@c.us', '628222222222@c.us'],
            DB::table('TChatD')->where('IdChat', 'stale-chat')->orderBy('IdPesanWaha')->pluck('PengirimIdWaha')->all(),
        );
    }

    /** @return array<string, mixed> */
    private function groupPayload(string $messageId, string $groupJid, string $participantJid, string $participantName): array
    {
        return [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => $messageId,
                'chatId' => $groupJid,
                'from' => $groupJid,
                'isGroup' => true,
                'participant' => $participantJid,
                'sender' => [
                    'id' => $participantJid,
                    'pushname' => $participantName,
                ],
                'body' => 'Pesan dari '.$participantName,
                'type' => 'chat',
                'timestamp' => 1760000000,
            ],
        ];
    }
}
