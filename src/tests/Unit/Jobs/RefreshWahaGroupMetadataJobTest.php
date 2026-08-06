<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RefreshWahaGroupMetadataJob;
use App\Jobs\SendBroadcastDebouncedJob;
use App\Services\Waha\WahaSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RefreshWahaGroupMetadataJobTest extends TestCase
{
    private const SESSION = 'session-a';
    private const GROUP_JID = '120363777777777777@g.us';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('MGrupWhatsapp');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('MSesiWhatsapp');
        Cache::flush();
        parent::tearDown();
    }

    public function test_success_updates_only_matching_session_group_siblings_and_dispatches_one_broadcast(): void
    {
        $this->seedChats();
        Queue::fake();

        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')
            ->once()
            ->with(self::SESSION, self::GROUP_JID)
            ->andReturn(['ok' => true, 'subject' => 'Updated Group Name', 'body' => json_encode(['subject' => 'Updated Group Name'])]);

        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);

        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('NamaGrupWhatsapp'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('GroupName'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('NamaGrupWhatsapp'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('GroupName'));
        self::assertSame('Session B Name', DB::table('TChat')->where('Id', 'chat-b-same-jid')->value('NamaGrupWhatsapp'));
        self::assertSame('Session B Name', DB::table('TChat')->where('Id', 'chat-b-same-jid')->value('GroupName'));
        self::assertSame('Different Group Name', DB::table('TChat')->where('Id', 'chat-a-other-jid')->value('NamaGrupWhatsapp'));
        self::assertSame('Different Group Name', DB::table('TChat')->where('Id', 'chat-a-other-jid')->value('GroupName'));
        self::assertSame('Private Name', DB::table('TChat')->where('Id', 'chat-a-private')->value('NamaGrupWhatsapp'));
        self::assertSame('Private Name', DB::table('TChat')->where('Id', 'chat-a-private')->value('GroupName'));

        Queue::assertPushedOn('broadcasts', SendBroadcastDebouncedJob::class, fn (SendBroadcastDebouncedJob $job): bool => $job->chatId === 'chat-a-first');
        Queue::assertPushedTimes(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_missing_subject_does_not_update_or_dispatch_a_broadcast(): void
    {
        $this->seedChats();
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'body' => json_encode(['id' => self::GROUP_JID])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        $this->assertMatchingSiblingsRemainUnchanged();
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
    }

    public function test_non_success_response_does_not_update_or_dispatch_a_broadcast(): void
    {
        $this->seedChats();
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => false, 'status' => 503, 'error' => 'metadata unavailable', 'body' => '']);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        $this->assertMatchingSiblingsRemainUnchanged();
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
    }

    public function test_duplicate_job_is_not_suppressed_by_lock(): void
    {
        $this->seedChats();
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->twice()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Updated Group Name', 'body' => json_encode(['subject' => 'Updated Group Name'])]);
        $job = new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID);
        $job->handle($wahaSender);
        $job->handle($wahaSender);
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('NamaGrupWhatsapp'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('GroupName'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('NamaGrupWhatsapp'));
        self::assertSame('Updated Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('GroupName'));
        Queue::assertPushedTimes(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_failure_logging_uses_safe_context_and_job_has_retry_properties(): void
    {
        $job = new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID);
        self::assertSame('waha-metadata', $job->queue);
        self::assertSame(3, $job->tries);
        self::assertSame(20, $job->timeout);
        self::assertSame([30, 120], $job->backoff());
        Log::spy();
        $job->failed(new RuntimeException('metadata request failed'));
        Log::shouldHaveReceived('warning')->once()->with('WAHA group metadata job failed.', ['group_jid' => self::GROUP_JID, 'exception' => RuntimeException::class]);
    }

    public function test_null_group_name_is_populated_from_subject(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-null', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => null, 'GroupName' => null, 'IdWahaTerdeteksi' => self::GROUP_JID]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['subject' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-null')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-null')->value('GroupName'));
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_empty_group_name_is_populated_from_subject(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-empty', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => '', 'GroupName' => '', 'IdWahaTerdeteksi' => self::GROUP_JID]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['subject' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-empty')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-empty')->value('GroupName'));
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_outdated_group_name_is_refreshed_from_subject(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-outdated', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => 'Old Name', 'GroupName' => 'Old Name', 'IdWahaTerdeteksi' => self::GROUP_JID]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['subject' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-outdated')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-outdated')->value('GroupName'));
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_up_to_date_group_name_skips_write_and_broadcast(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-up-to-date', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => 'Bilka Official Group', 'GroupName' => 'Bilka Official Group', 'IdWahaTerdeteksi' => self::GROUP_JID]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['subject' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-up-to-date')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-up-to-date')->value('GroupName'));
        Queue::assertNotPushed(SendBroadcastDebouncedJob::class);
    }

    public function test_nomor_whatsapp_only_row_is_updated(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-nomor-only', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => null, 'GroupName' => null, 'IdWahaTerdeteksi' => null]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['name' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-nomor-only')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-nomor-only')->value('GroupName'));
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    public function test_legacy_nama_grup_matches_but_group_name_is_null_still_updates_group_name(): void
    {
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-id-a', 'KodeSesi' => self::SESSION]);
        DB::table('TChat')->insert(['Id' => 'chat-legacy-only', 'IdSesiWhatsapp' => 'session-id-a', 'JenisChat' => 'Grup', 'NomorWhatsapp' => self::GROUP_JID, 'NamaGrupWhatsapp' => 'Bilka Official Group', 'GroupName' => null, 'IdWahaTerdeteksi' => self::GROUP_JID]);
        Queue::fake();
        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('getGroupMetadata')->once()->with(self::SESSION, self::GROUP_JID)->andReturn(['ok' => true, 'subject' => 'Bilka Official Group', 'body' => json_encode(['subject' => 'Bilka Official Group'])]);
        (new RefreshWahaGroupMetadataJob(self::SESSION, self::GROUP_JID))->handle($wahaSender);
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-legacy-only')->value('NamaGrupWhatsapp'));
        self::assertSame('Bilka Official Group', DB::table('TChat')->where('Id', 'chat-legacy-only')->value('GroupName'));
        Queue::assertPushed(SendBroadcastDebouncedJob::class, 1);
    }

    private function createTables(): void
    {
        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi')->unique();
        });
        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdSesiWhatsapp')->nullable();
            $table->string('JenisChat');
            $table->string('NomorWhatsapp')->nullable();
            $table->string('NamaGrupWhatsapp')->nullable();
            $table->string('GroupName')->nullable();
            $table->string('IdWahaTerdeteksi')->nullable();
            $table->string('IdGrupWhatsapp')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });
        Schema::create('MGrupWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdGrupWaha')->nullable();
            $table->string('NamaGrup')->nullable();
            $table->boolean('NonAktif')->default(false);
            $table->dateTime('TglEdit')->nullable();
        });
    }

    private function seedChats(): void
    {
        DB::table('MSesiWhatsapp')->insert([['Id' => 'session-id-a', 'KodeSesi' => self::SESSION], ['Id' => 'session-id-b', 'KodeSesi' => 'session-b']]);
        DB::table('TChat')->insert([
            $this->chat('chat-a-first', 'session-id-a', 'Grup', self::GROUP_JID, 'First Group Name'),
            $this->chat('chat-a-sibling', 'session-id-a', 'Grup', self::GROUP_JID, 'Sibling Group Name'),
            $this->chat('chat-b-same-jid', 'session-id-b', 'Grup', self::GROUP_JID, 'Session B Name'),
            $this->chat('chat-a-other-jid', 'session-id-a', 'Grup', '120363888888888888@g.us', 'Different Group Name'),
            $this->chat('chat-a-private', 'session-id-a', 'Pribadi', self::GROUP_JID, 'Private Name'),
        ]);
    }

    private function assertMatchingSiblingsRemainUnchanged(): void
    {
        self::assertSame('First Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('NamaGrupWhatsapp'));
        self::assertSame('First Group Name', DB::table('TChat')->where('Id', 'chat-a-first')->value('GroupName'));
        self::assertSame('Sibling Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('NamaGrupWhatsapp'));
        self::assertSame('Sibling Group Name', DB::table('TChat')->where('Id', 'chat-a-sibling')->value('GroupName'));
    }

    /** @return array<string, string> */
    private function chat(string $id, string $sessionId, string $jenisChat, string $groupJid, string $name): array
    {
        return ['Id' => $id, 'IdSesiWhatsapp' => $sessionId, 'JenisChat' => $jenisChat, 'NomorWhatsapp' => $groupJid, 'NamaGrupWhatsapp' => $name, 'GroupName' => $name, 'IdWahaTerdeteksi' => $groupJid];
    }
}