<?php

namespace Tests\Feature\Waha;

use App\Jobs\SendBroadcastDebouncedJob;
use App\Jobs\SyncWahaChatIdentityJob;
use App\Jobs\ProcessAiAutoReplyJob;
use App\Jobs\ProcessWebhookJob;
use App\Services\Waha\WahaWebhookProcessor;
use App\Services\Waha\WahaSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WahaChatIdentitySyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Schema::create('TLogIntegrasi', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeIntegrasi');
            $table->string('UrlEndpoint')->nullable();
            $table->string('MetodeHttp')->nullable();
            $table->text('RequestJson')->nullable();
            $table->text('ResponseJson')->nullable();
            $table->integer('StatusHttp')->nullable();
            $table->boolean('Berhasil')->nullable();
            $table->text('PesanError')->nullable();
            $table->dateTime('TglRequest')->nullable();
            $table->dateTime('TglResponse')->nullable();
            $table->dateTime('TglBuat')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });

        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi');
        });

        Schema::create('MGrupWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdGrupWaha')->nullable();
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdSesiWhatsapp');
            $table->string('IdGrupWhatsapp')->nullable();
            $table->string('JenisChat');
            $table->string('NomorWhatsapp');
            $table->string('IdWahaTerdeteksi', 200)->nullable();
            $table->string('NomorWhatsappTerdeteksi')->nullable();
            $table->string('NamaKontakWaha', 150)->nullable();
            $table->string('NamaGrupWaha', 200)->nullable();
            $table->string('UrlFotoProfil', 1000)->nullable();
            $table->dateTime('TglFotoProfilDiambil')->nullable();
            $table->dateTime('TglIdentitasWahaDiambil')->nullable();
            $table->string('StatusIdentitasWaha', 30)->nullable();
            $table->string('PesanErrorIdentitasWaha', 500)->nullable();
            $table->dateTime('TglBuat');
            $table->dateTime('TglEdit')->nullable();
        });

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->string('ArahPesan')->nullable();
            $table->text('PayloadJson')->nullable();
            $table->string('PengirimIdWaha', 200)->nullable();
            $table->string('UrlFotoProfilPengirim', 1000)->nullable();
            $table->dateTime('TglFotoProfilPengirimDiambil')->nullable();
            $table->boolean('DikirimOlehCustomer')->default(false);
            $table->dateTime('TglPesan')->nullable();
        });

        config()->set('services.waha.base_url', 'https://waha.test');
        config()->set('services.waha.api_key', 'test-api-key');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('MGrupWhatsapp');
        Schema::dropIfExists('MSesiWhatsapp');
        Schema::dropIfExists('TLogIntegrasi');
        Cache::flush();

        parent::tearDown();
    }

    public function test_group_identity_sync_uses_raw_group_jid_and_broadcasts_only_when_snapshot_changes(): void
    {
        $this->insertSession();
        DB::table('MGrupWhatsapp')->insert([
            'Id' => 'group-mapping-stale',
            'IdGrupWaha' => '120363000@g.us',
            'NonAktif' => false,
        ]);
        $this->insertChat([
            'Id' => 'group-chat',
            'IdGrupWhatsapp' => 'group-mapping-stale',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363111@g.us',
            'IdWahaTerdeteksi' => '628111111111@c.us',
            'NamaGrupWaha' => 'Nama Grup Lama',
            'UrlFotoProfil' => 'https://waha.test/old-group.jpg',
        ]);
        DB::table('TChatD')->insert([
            'Id' => 'group-message',
            'IdChat' => 'group-chat',
            'PayloadJson' => json_encode([
                'chatId' => '120363111@g.us',
                'participant' => '628111111111@c.us',
            ]),
            'TglPesan' => now(),
        ]);
        Http::fake([
            'https://waha.test/api/default/groups/120363111@g.us' => Http::response(['subject' => 'Grup WAHA Baru']),
            'https://waha.test/api/contacts/profile-picture*' => Http::response(['profilePictureURL' => 'https://waha.test/group.jpg']),
        ]);
        Queue::fake();

        app(SyncWahaChatIdentityJob::class, ['chatId' => 'group-chat'])->handle(app(WahaSender::class));

        self::assertDatabaseHas('TChat', [
            'Id' => 'group-chat',
            'IdWahaTerdeteksi' => '120363111@g.us',
            'NamaGrupWaha' => 'Grup WAHA Baru',
            'UrlFotoProfil' => 'https://waha.test/group.jpg',
            'StatusIdentitasWaha' => 'success',
        ]);
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/groups/120363111@g.us');
        });
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'contactId=120363111%40g.us')
                && ! str_contains($request->url(), '628111111111');
        });

        app(SyncWahaChatIdentityJob::class, ['chatId' => 'group-chat'])->handle(app(WahaSender::class));

        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_group_identity_sync_updates_recent_participant_profiles_and_broadcasts_once(): void
    {
        $this->insertSession();
        $this->insertChat([
            'Id' => 'participant-chat',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363111@g.us',
            'NamaGrupWaha' => 'Grup Lama',
            'UrlFotoProfil' => 'https://waha.test/group-old.jpg',
        ]);
        $oldPhotoTakenAt = now()->subDays(2);
        DB::table('TChatD')->insert([
            [
                'Id' => 'participant-old-photo',
                'IdChat' => 'participant-chat',
                'ArahPesan' => 'Masuk',
                'PengirimIdWaha' => '628111111111@c.us',
                'UrlFotoProfilPengirim' => 'https://waha.test/participant-old.jpg',
                'TglFotoProfilPengirimDiambil' => $oldPhotoTakenAt,
                'DikirimOlehCustomer' => true,
                'TglPesan' => now()->subMinutes(2),
            ],
            [
                'Id' => 'participant-no-photo',
                'IdChat' => 'participant-chat',
                'ArahPesan' => 'Masuk',
                'PengirimIdWaha' => '628111111111@c.us',
                'DikirimOlehCustomer' => true,
                'TglPesan' => now()->subMinute(),
            ],
            [
                'Id' => 'participant-fresh-photo',
                'IdChat' => 'participant-chat',
                'ArahPesan' => 'Masuk',
                'PengirimIdWaha' => '628222222222@c.us',
                'UrlFotoProfilPengirim' => 'https://waha.test/participant-fresh.jpg',
                'TglFotoProfilPengirimDiambil' => now(),
                'DikirimOlehCustomer' => true,
                'TglPesan' => now(),
            ],
        ]);
        $participantRequests = 0;
        Http::fake(function (Request $request) use (&$participantRequests) {
            if (str_contains($request->url(), '/groups/')) {
                return Http::response(['subject' => 'Grup Baru']);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $contactId = (string) ($query['contactId'] ?? '');

            if ($contactId === '628111111111@c.us') {
                $participantRequests++;

                return Http::response(['profilePictureURL' => 'https://waha.test/participant-new.jpg']);
            }

            return Http::response(['profilePictureURL' => 'https://waha.test/group-new.jpg']);
        });
        Queue::fake();

        app(SyncWahaChatIdentityJob::class, ['chatId' => 'participant-chat'])->handle(app(WahaSender::class));

        self::assertSame(1, $participantRequests);
        self::assertDatabaseHas('TChat', [
            'Id' => 'participant-chat',
            'UrlFotoProfil' => 'https://waha.test/group-new.jpg',
            'StatusIdentitasWaha' => 'success',
        ]);
        self::assertDatabaseHas('TChatD', [
            'Id' => 'participant-old-photo',
            'UrlFotoProfilPengirim' => 'https://waha.test/participant-new.jpg',
        ]);
        self::assertDatabaseHas('TChatD', [
            'Id' => 'participant-no-photo',
            'UrlFotoProfilPengirim' => 'https://waha.test/participant-new.jpg',
        ]);
        self::assertDatabaseHas('TChatD', [
            'Id' => 'participant-fresh-photo',
            'UrlFotoProfilPengirim' => 'https://waha.test/participant-fresh.jpg',
        ]);
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_participant_profile_failure_preserves_snapshots_and_deduplicates_for_one_hour(): void
    {
        $this->insertSession();
        $this->insertChat([
            'Id' => 'participant-failure-chat',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363111@g.us',
            'IdWahaTerdeteksi' => '120363111@g.us',
            'NamaGrupWaha' => 'Grup Tetap',
            'UrlFotoProfil' => 'https://waha.test/group-last.jpg',
        ]);
        $oldPhotoTakenAt = now()->subDays(2)->format('Y-m-d H:i:s');
        DB::table('TChatD')->insert([
            'Id' => 'participant-failure-message',
            'IdChat' => 'participant-failure-chat',
            'ArahPesan' => 'Masuk',
            'PengirimIdWaha' => '628333333333@c.us',
            'UrlFotoProfilPengirim' => 'https://waha.test/participant-last.jpg',
            'TglFotoProfilPengirimDiambil' => $oldPhotoTakenAt,
            'DikirimOlehCustomer' => true,
            'TglPesan' => now(),
        ]);
        $participantRequests = 0;
        Http::fake(function (Request $request) use (&$participantRequests) {
            if (str_contains($request->url(), '/groups/')) {
                return Http::response(['subject' => 'Grup Tetap']);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['contactId'] ?? null) === '628333333333@c.us') {
                $participantRequests++;
            }

            return Http::response(['error' => 'profile unavailable'], 503);
        });
        Queue::fake();

        $job = app(SyncWahaChatIdentityJob::class, ['chatId' => 'participant-failure-chat']);
        $job->handle(app(WahaSender::class));
        $job->handle(app(WahaSender::class));

        $chat = DB::table('TChat')->where('Id', 'participant-failure-chat')->first();
        $message = DB::table('TChatD')->where('Id', 'participant-failure-message')->first();

        self::assertSame(1, $participantRequests);
        self::assertTrue(Cache::has('waha:participant-profile:default:'.sha1('628333333333@c.us')));
        self::assertSame('success', $chat->StatusIdentitasWaha);
        self::assertSame('https://waha.test/group-last.jpg', $chat->UrlFotoProfil);
        self::assertSame('https://waha.test/participant-last.jpg', $message->UrlFotoProfilPengirim);
        self::assertSame($oldPhotoTakenAt, $message->TglFotoProfilPengirimDiambil);
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
    }

    public function test_group_identity_sync_requests_at_most_twenty_recent_participants(): void
    {
        $this->insertSession();
        $this->insertChat([
            'Id' => 'participant-limit-chat',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363222@g.us',
        ]);

        for ($index = 1; $index <= 21; $index++) {
            DB::table('TChatD')->insert([
                'Id' => 'participant-limit-'.$index,
                'IdChat' => 'participant-limit-chat',
                'ArahPesan' => 'Masuk',
                'PengirimIdWaha' => '628'.str_pad((string) $index, 10, '0', STR_PAD_LEFT).'@c.us',
                'DikirimOlehCustomer' => true,
                'TglPesan' => now()->subSeconds(21 - $index),
            ]);
        }

        $participantRequests = 0;
        Http::fake(function (Request $request) use (&$participantRequests) {
            if (str_contains($request->url(), '/groups/')) {
                return Http::response(['subject' => 'Grup Limit']);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (! str_ends_with((string) ($query['contactId'] ?? ''), '@g.us')) {
                $participantRequests++;
            }

            return Http::response(['profilePictureURL' => 'https://waha.test/participant.jpg']);
        });

        app(SyncWahaChatIdentityJob::class, ['chatId' => 'participant-limit-chat'])->handle(app(WahaSender::class));

        self::assertSame(20, $participantRequests);
    }

    public function test_personal_lid_identity_sync_preserves_lid_and_stores_resolved_phone(): void
    {
        $this->insertSession();
        $this->insertChat([
            'Id' => 'lid-chat',
            'JenisChat' => 'Pribadi',
            'NomorWhatsapp' => '111',
            'IdWahaTerdeteksi' => '111@lid',
        ]);
        Http::fake([
            'https://waha.test/api/default/contacts/111@lid' => Http::response(['pushname' => 'Kontak LID']),
            'https://waha.test/api/default/lids/111@lid' => Http::response(['pn' => '628111111111@c.us']),
            'https://waha.test/api/contacts/profile-picture*' => Http::response(['profilePictureURL' => 'https://waha.test/lid.jpg']),
        ]);

        app(SyncWahaChatIdentityJob::class, ['chatId' => 'lid-chat'])->handle(app(WahaSender::class));

        self::assertDatabaseHas('TChat', [
            'Id' => 'lid-chat',
            'IdWahaTerdeteksi' => '111@lid',
            'NomorWhatsappTerdeteksi' => '628111111111',
            'NamaKontakWaha' => 'Kontak LID',
            'UrlFotoProfil' => 'https://waha.test/lid.jpg',
            'StatusIdentitasWaha' => 'success',
        ]);
    }

    public function test_failed_identity_sync_keeps_existing_snapshot_and_sanitizes_error(): void
    {
        $this->insertSession();
        $this->insertChat([
            'Id' => 'failed-chat',
            'JenisChat' => 'Pribadi',
            'NomorWhatsapp' => '628222222222',
            'NamaKontakWaha' => 'Snapshot Lama',
            'UrlFotoProfil' => 'https://waha.test/old.jpg',
        ]);
        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response(['error' => 'provider-secret'], 500),
        ]);
        Queue::fake();

        try {
            app(SyncWahaChatIdentityJob::class, ['chatId' => 'failed-chat'])->handle(app(WahaSender::class));
            self::fail('The job must fail so Laravel retries metadata synchronization.');
        } catch (\RuntimeException $exception) {
            self::assertSame('WAHA identity metadata synchronization failed.', $exception->getMessage());
        }

        $chat = DB::table('TChat')->where('Id', 'failed-chat')->first();
        self::assertSame('Snapshot Lama', $chat->NamaKontakWaha);
        self::assertSame('https://waha.test/old.jpg', $chat->UrlFotoProfil);
        self::assertSame('failed', $chat->StatusIdentitasWaha);
        self::assertNotNull($chat->TglIdentitasWahaDiambil);
        self::assertStringNotContainsString('provider-secret', (string) $chat->PesanErrorIdentitasWaha);
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
    }

    public function test_identity_sync_dispatch_is_debounced_for_sixty_seconds(): void
    {
        Queue::fake();

        SyncWahaChatIdentityJob::dispatchDebounced('deduped-chat');
        SyncWahaChatIdentityJob::dispatchDebounced('deduped-chat');

        Queue::assertPushed(SyncWahaChatIdentityJob::class, function (SyncWahaChatIdentityJob $job): bool {
            return $job->chatId === 'deduped-chat'
                && $job->queue === 'webhooks'
                && $job->tries === 3
                && $job->timeout === 30
                && $job->backoff() === [30, 120];
        });
        Queue::assertPushed(SyncWahaChatIdentityJob::class, 1);
    }

    public function test_webhook_dispatches_identity_sync_only_after_successful_nonduplicate_processing(): void
    {
        Queue::fake();
        $processor = \Mockery::mock(WahaWebhookProcessor::class);
        $processor->shouldReceive('process')->once()->with(['event' => 'message'])->andReturn([
            'ok' => true,
            'chat_id' => 'webhook-chat',
        ]);

        (new ProcessWebhookJob(['event' => 'message']))->handle($processor);

        Queue::assertPushed(SyncWahaChatIdentityJob::class, 1);
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
        Queue::assertPushed(ProcessAiAutoReplyJob::class, 1);
    }

    public function test_duplicate_webhook_does_not_dispatch_identity_sync(): void
    {
        Queue::fake();
        $processor = \Mockery::mock(WahaWebhookProcessor::class);
        $processor->shouldReceive('process')->once()->andReturn([
            'ok' => true,
            'duplicate' => true,
            'chat_id' => 'webhook-chat',
        ]);

        (new ProcessWebhookJob(['event' => 'message']))->handle($processor);

        Queue::assertNotPushed(SyncWahaChatIdentityJob::class);
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
        Queue::assertNotPushed(ProcessAiAutoReplyJob::class);
    }

    public function test_ignored_webhook_does_not_dispatch_identity_sync(): void
    {
        Queue::fake();
        $processor = \Mockery::mock(WahaWebhookProcessor::class);
        $processor->shouldReceive('process')->once()->andReturn([
            'ok' => true,
            'ignored' => true,
            'chat_id' => 'webhook-chat',
        ]);

        (new ProcessWebhookJob(['event' => 'message']))->handle($processor);

        Queue::assertNotPushed(SyncWahaChatIdentityJob::class);
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
        Queue::assertNotPushed(ProcessAiAutoReplyJob::class);
    }

    public function test_contact_metadata_is_normalized_projected_and_length_limited(): void
    {
        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response([
                'name' => str_repeat('N', 151),
                'pushname' => str_repeat('P', 151),
                'id' => '628123456789@s.whatsapp.net',
                'phone' => '+62 812-3456-789',
                'secret' => 'provider-secret',
            ]),
        ]);

        $result = app(WahaSender::class)->getContactInfo('default', '628123456789@s.whatsapp.net');

        self::assertSame(true, $result['ok']);
        self::assertSame(str_repeat('N', 150), $result['name']);
        self::assertSame(str_repeat('P', 150), $result['pushname']);
        self::assertSame('628123456789@c.us', $result['id']);
        self::assertSame('628123456789', $result['phone']);
        self::assertSame(200, $result['status']);
        self::assertNull($result['error']);
        self::assertArrayNotHasKey('body', $result);
        self::assertArrayNotHasKey('secret', $result);
        $this->assertNoProviderSecretInIntegrationLogs();

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://waha.test/api/default/contacts/628123456789@c.us';
        });
    }

    public function test_group_metadata_uses_subject_and_schema_length_limit(): void
    {
        Http::fake([
            'https://waha.test/api/default/groups/*' => Http::response([
                'subject' => str_repeat('G', 201),
                'id' => '120363000000000001@g.us',
                'token' => 'provider-secret',
            ]),
        ]);

        $result = app(WahaSender::class)->getGroupInfo('default', '120363000000000001@g.us');

        self::assertSame([
            'ok' => true,
            'name' => str_repeat('G', 200),
            'id' => '120363000000000001@g.us',
            'status' => 200,
            'error' => null,
        ], $result);
        $this->assertNoProviderSecretInIntegrationLogs();
    }

    public function test_profile_picture_response_secret_is_not_persisted_while_url_is_parsed(): void
    {
        Http::fake([
            'https://waha.test/api/contacts/profile-picture*' => Http::response([
                'profilePictureURL' => 'https://cdn.waha.test/profile.jpg',
                'secret' => 'profile-provider-secret',
            ]),
        ]);

        $result = app(WahaSender::class)->getContactProfilePictureUrl('default', '628123456789@c.us');

        self::assertTrue($result['ok']);
        self::assertSame('https://cdn.waha.test/profile.jpg', $result['url']);
        $this->assertNoProviderSecretInIntegrationLogs();
    }

    public function test_metadata_name_limit_uses_utf16_code_units_without_splitting_surrogate_pairs(): void
    {
        $contactName = str_repeat("\u{1F600}", 75).'A';
        $groupName = str_repeat("\u{1F600}", 100).'A';

        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response(['name' => $contactName]),
            'https://waha.test/api/default/groups/*' => Http::response(['subject' => $groupName]),
        ]);

        $contact = app(WahaSender::class)->getContactInfo('default', '628123456791');
        $group = app(WahaSender::class)->getGroupInfo('default', '120363000000000003@g.us');

        self::assertSame(str_repeat("\u{1F600}", 75), $contact['name']);
        self::assertSame(str_repeat("\u{1F600}", 100), $group['name']);
    }

    public function test_unknown_metadata_response_returns_sanitized_failure_without_raw_body(): void
    {
        Http::fake([
            'https://waha.test/api/default/groups/*' => Http::response([
                'unexpected' => 'shape',
                'token' => 'provider-secret',
            ]),
        ]);

        $result = app(WahaSender::class)->getGroupInfo('default', '120363000000000002@g.us');

        self::assertFalse($result['ok']);
        self::assertNull($result['name']);
        self::assertSame('120363000000000002@g.us', $result['id']);
        self::assertSame(200, $result['status']);
        self::assertNotSame('provider-secret', $result['error']);
        self::assertArrayNotHasKey('body', $result);
        $this->assertNoProviderSecretInIntegrationLogs();
    }

    public function test_empty_contact_metadata_response_returns_sanitized_failure(): void
    {
        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response([]),
        ]);

        $result = app(WahaSender::class)->getContactInfo('default', '628123456790');

        self::assertFalse($result['ok']);
        self::assertNull($result['name']);
        self::assertNull($result['pushname']);
        self::assertSame('628123456790@c.us', $result['id']);
        self::assertNull($result['phone']);
        self::assertSame(200, $result['status']);
        self::assertArrayNotHasKey('body', $result);
    }

    public function test_lid_contact_uses_resolved_phone_number_when_available(): void
    {
        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response([
                'pushname' => 'Lid Contact',
            ]),
            'https://waha.test/api/default/lids/*' => Http::response([
                'pn' => '628777777777@c.us',
            ]),
        ]);

        $result = app(WahaSender::class)->getContactInfo('default', '123456789@lid');

        self::assertSame(true, $result['ok']);
        self::assertSame('Lid Contact', $result['name']);
        self::assertSame('Lid Contact', $result['pushname']);
        self::assertSame('123456789@lid', $result['id']);
        self::assertSame('628777777777', $result['phone']);
    }

    public function test_lid_contact_keeps_metadata_when_phone_lookup_fails_without_exposing_response(): void
    {
        Http::fake([
            'https://waha.test/api/default/contacts/*' => Http::response([
                'name' => 'Lid Contact',
            ]),
            'https://waha.test/api/default/lids/*' => Http::response([
                'error' => 'provider-secret',
            ], 404),
        ]);

        $result = app(WahaSender::class)->getContactInfo('default', '123456790@lid');

        self::assertSame(true, $result['ok']);
        self::assertSame('Lid Contact', $result['name']);
        self::assertSame('123456790@lid', $result['id']);
        self::assertNull($result['phone']);
        self::assertNull($result['error']);
        self::assertArrayNotHasKey('body', $result);
        $this->assertNoProviderSecretInIntegrationLogs();
    }

    public function test_metadata_exception_and_endpoint_url_are_redacted(): void
    {
        config()->set('services.waha.base_url', 'https://waha.test?access_token=url-secret');
        Http::fake(function (Request $request): never {
            throw new ConnectionException('WAHA request failed: api_key=exception-secret');
        });

        $result = app(WahaSender::class)->getContactInfo('default', '628123456792');
        $log = DB::table('TLogIntegrasi')->first();

        self::assertFalse($result['ok']);
        self::assertStringNotContainsString('exception-secret', (string) $result['error']);
        self::assertStringNotContainsString('url-secret', (string) $log->UrlEndpoint);
        self::assertStringNotContainsString('exception-secret', (string) $log->PesanError);
    }

    private function assertNoProviderSecretInIntegrationLogs(): void
    {
        self::assertSame(0, DB::table('TLogIntegrasi')
            ->where('ResponseJson', 'like', '%provider-secret%')
            ->orWhere('PesanError', 'like', '%provider-secret%')
            ->count());
    }

    private function insertSession(): void
    {
        DB::table('MSesiWhatsapp')->insert([
            'Id' => 'session-1',
            'KodeSesi' => 'default',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function insertChat(array $attributes): void
    {
        DB::table('TChat')->insert(array_merge([
            'Id' => 'chat-1',
            'IdSesiWhatsapp' => 'session-1',
            'IdGrupWhatsapp' => null,
            'JenisChat' => 'Pribadi',
            'NomorWhatsapp' => '628000000000',
            'IdWahaTerdeteksi' => null,
            'NomorWhatsappTerdeteksi' => null,
            'NamaKontakWaha' => null,
            'NamaGrupWaha' => null,
            'UrlFotoProfil' => null,
            'TglFotoProfilDiambil' => null,
            'TglIdentitasWahaDiambil' => null,
            'StatusIdentitasWaha' => null,
            'PesanErrorIdentitasWaha' => null,
            'TglBuat' => now(),
            'TglEdit' => null,
        ], $attributes));
    }
}
