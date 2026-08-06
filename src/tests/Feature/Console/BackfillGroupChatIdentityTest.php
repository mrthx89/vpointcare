<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillGroupChatIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('JenisChat');
            $table->string('IdWahaTerdeteksi')->nullable();
            $table->timestamp('TglEdit')->nullable();
        });
        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->text('PayloadJson')->nullable();
            $table->timestamp('TglPesan');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Cache::flush();

        parent::tearDown();
    }

    public function test_dry_run_reports_candidate_without_writing_identity(): void
    {
        $this->insertGroupChat('chat-dry-run');
        $this->insertPayload('detail-dry-run', 'chat-dry-run', '120363700000000001@g.us');

        Log::spy();
        $this->artisan('waha:backfill-group-chat-identity --dry-run')->assertExitCode(0);

        self::assertNull(DB::table('TChat')->where('Id', 'chat-dry-run')->value('IdWahaTerdeteksi'));
        Log::shouldHaveReceived('info')->once()->with(
            'waha.group_identity_backfill.completed',
            \Mockery::on(fn (array $context): bool => $context['dry_run'] === true && $context['candidate'] === 1 && $context['updated'] === 1),
        );
    }

    public function test_write_populates_only_missing_identity_and_is_idempotent(): void
    {
        $this->insertGroupChat('chat-existing', '120363700000000099@g.us');
        $this->insertGroupChat('chat-missing');
        $this->insertPayload('detail-existing', 'chat-existing', '120363700000000001@g.us');
        $this->insertPayload('detail-missing', 'chat-missing', '120363700000000002@g.us');

        $this->artisan('waha:backfill-group-chat-identity')->assertExitCode(0);

        self::assertSame('120363700000000099@g.us', DB::table('TChat')->where('Id', 'chat-existing')->value('IdWahaTerdeteksi'));
        self::assertSame('120363700000000002@g.us', DB::table('TChat')->where('Id', 'chat-missing')->value('IdWahaTerdeteksi'));
        self::assertSame(2, DB::table('TChat')->count());
        self::assertSame(2, DB::table('TChatD')->count());

        $this->artisan('waha:backfill-group-chat-identity')->assertExitCode(0);

        self::assertSame('120363700000000002@g.us', DB::table('TChat')->where('Id', 'chat-missing')->value('IdWahaTerdeteksi'));
        self::assertSame(2, DB::table('TChat')->count());
        self::assertSame(2, DB::table('TChatD')->count());
    }

    public function test_unparseable_payload_is_skipped_without_writing_identity(): void
    {
        $this->insertGroupChat('chat-unparseable');
        DB::table('TChatD')->insert([
            'Id' => 'detail-unparseable',
            'IdChat' => 'chat-unparseable',
            'PayloadJson' => '{invalid-json',
            'TglPesan' => now(),
        ]);

        $this->artisan('waha:backfill-group-chat-identity')->assertExitCode(0);

        self::assertNull(DB::table('TChat')->where('Id', 'chat-unparseable')->value('IdWahaTerdeteksi'));
        self::assertSame(1, DB::table('TChatD')->count());
    }

    private function insertGroupChat(string $id, ?string $identity = null): void
    {
        DB::table('TChat')->insert([
            'Id' => $id,
            'JenisChat' => 'Grup',
            'IdWahaTerdeteksi' => $identity,
        ]);
    }

    private function insertPayload(string $id, string $chatId, string $groupJid): void
    {
        DB::table('TChatD')->insert([
            'Id' => $id,
            'IdChat' => $chatId,
            'PayloadJson' => json_encode(['chatId' => $groupJid], JSON_THROW_ON_ERROR),
            'TglPesan' => now(),
        ]);
    }
}
