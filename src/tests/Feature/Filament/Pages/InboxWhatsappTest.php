<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\InboxWhatsapp;
use App\Jobs\SyncWahaChatIdentityJob;
use App\Models\Master\Pengguna;
use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaSender;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InboxWhatsappTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->name('inbox-whatsapp-test.livewire.update');
        });
        app('router')->getRoutes()->refreshNameLookups();
        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        foreach ([
            'TChatDCatatanInternal',
            'TChatD',
            'TChat',
            'MPengguna',
            'MStatusChat',
            'MSesiWhatsapp',
            'MGrupWhatsapp',
            'MNomorWhatsapp',
            'MCustomer',
            'MInstansi',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Cache::flush();

        parent::tearDown();
    }

    public function test_identity_mode_defaults_to_whatsapp_and_rejects_invalid_value(): void
    {
        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->assertSet('identityDisplayMode', 'whatsapp')
            ->set('identityDisplayMode', 'invalid')
            ->assertSet('identityDisplayMode', 'whatsapp');
    }

    public function test_inbox_requires_view_permission(): void
    {
        $this->actingAs($this->agent([]));

        self::assertFalse(InboxWhatsapp::canAccess());
    }

    public function test_group_identity_keeps_group_chat_separate_from_member_sender_and_exposes_media_routes(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $component->assertStatus(200);
        $state = $component->getData();
        $chatRows = $state['chatRows'] ?? null;
        $messages = $state['messages'] ?? null;
        $selectedChat = $state['selectedChat'] ?? null;
        $encodedMedia = base64_encode("%PDF-1.4\n%%EOF");

        self::assertIsArray($chatRows);
        self::assertNotEmpty($chatRows);
        self::assertArrayHasKey('Identity', $chatRows[0]);
        self::assertSame('120363999999999999@g.us', $chatRows[0]['Identity']['whatsapp']['ChatId']);
        self::assertSame('120363999999999999@g.us', $chatRows[0]['Identity']['whatsapp']['GroupId']);
        self::assertSame('WAHA Support Group', $chatRows[0]['Identity']['whatsapp']['GroupName']);
        self::assertSame('WAHA Support Group', $chatRows[0]['Identity']['whatsapp']['PrimaryName']);
        self::assertSame('Internal Support Group', $chatRows[0]['Identity']['internal']['GroupName']);
        self::assertIsArray($selectedChat);
        self::assertArrayHasKey('Identity', $selectedChat);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['whatsapp']['ChatId']);
        self::assertSame('WAHA Support Group', $selectedChat['Identity']['whatsapp']['GroupName']);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['whatsapp']['GroupId']);
        self::assertSame('Internal Support Group', $selectedChat['Identity']['internal']['GroupName']);
        self::assertNotSame('628222222222@c.us', $selectedChat['Identity']['whatsapp']['ChatId']);
        self::assertNotSame('628222222222@c.us', $selectedChat['Identity']['whatsapp']['GroupId']);
        self::assertSame('Alice Raw', $messages[0]['SenderName']);
        self::assertSame('628222222222', $messages[0]['SenderNumber']);
        self::assertSame('https://waha.test/profiles/alice.jpg', $messages[0]['SenderAvatarUrl']);
        self::assertNull($messages[0]['UrlMedia']);
        self::assertStringContainsString('/admin/waha-media/message-group-1', $messages[0]['MediaUrl']);
        self::assertStringContainsString('download=1', $messages[0]['MediaDownloadUrl']);
        self::assertStringNotContainsString($encodedMedia, json_encode([
            'messages' => $messages,
            'selectedChat' => $selectedChat,
            'chatRows' => $chatRows,
        ], JSON_THROW_ON_ERROR));
    }

    public function test_renders_localized_identity_controls_group_sender_and_media_download(): void
    {
        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->assertSee('WhatsApp asli')
            ->assertSee('Data internal')
            ->assertSee('Grup WhatsApp')
            ->assertSee('Chat Pribadi')
            ->assertSee('WAHA Support Group')
            ->assertSee('Unduh media')
            ->assertSee('120363999999999999@g.us')
            ->assertSee('Alice Raw')
            ->assertSee('628222222222')
            ->assertSeeHtml('role=')
            ->assertSeeHtml('aria-pressed=')
            ->assertSeeHtml('focus-visible:ring-2')
            ->assertSeeHtml('aria-label=')
            ->assertSeeHtml('wire:click="$set(\'identityDisplayMode\', \'whatsapp\')"')
            ->assertSeeHtml('wire:click="$set(\'identityDisplayMode\', \'internal\')"')
            ->assertSeeHtml('download=1');

        app()->setLocale('en');

        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->assertSee('Original WhatsApp')
            ->assertSee('Internal data')
            ->assertSee('WAHA Support Group')
            ->assertSee('Download media');
    }

    public function test_whatsapp_identity_uses_snapshot_lid_and_resolved_number_before_internal_mapping(): void
    {
        DB::table('TChat')->where('Id', 'chat-personal-1')->update([
            'NomorWhatsapp' => '199999999999999@lid',
            'IdWahaTerdeteksi' => '199999999999999@lid',
            'NomorWhatsappTerdeteksi' => '628333333333',
        ]);

        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->set('filterType', 'pribadi');
        $chatRows = $component->getData()['chatRows'] ?? [];

        self::assertCount(1, $chatRows);
        self::assertSame('WAHA Personal Contact', $chatRows[0]['Identity']['whatsapp']['PrimaryName']);
        self::assertSame('WAHA Personal Contact', $chatRows[0]['Identity']['whatsapp']['ContactName']);
        self::assertSame('199999999999999@lid', $chatRows[0]['Identity']['whatsapp']['ChatId']);
        self::assertSame('628333333333', $chatRows[0]['Identity']['whatsapp']['ContactNumber']);
        self::assertSame('Mapped Personal Contact', $chatRows[0]['Identity']['internal']['ContactName']);
        self::assertSame('628444444444', $chatRows[0]['Identity']['internal']['ContactNumber']);
    }

    public function test_refresh_identity_requires_manage_permission_and_dispatches_async_without_waha_call(): void
    {
        Queue::fake();
        DB::table('TChat')->where('Id', 'chat-group-1')->update([
            'TglFotoProfilDiambil' => now(),
        ]);

        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldNotReceive('getContactProfilePictureUrl');
        $this->app->instance(WahaSender::class, $wahaSender);

        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->call('refreshProfilWaha')
            ->assertForbidden();

        Queue::assertNotPushed(SyncWahaChatIdentityJob::class);

        Livewire::actingAs($this->agent([
            AccessPermissions::INBOX_VIEW,
            AccessPermissions::INBOX_MANAGE,
        ]))
            ->test(InboxWhatsapp::class)
            ->call('refreshProfilWaha')
            ->assertStatus(200);

        Queue::assertPushed(SyncWahaChatIdentityJob::class, function (SyncWahaChatIdentityJob $job): bool {
            return $job->chatId === 'chat-group-1';
        });
    }

    public function test_selecting_group_chat_keeps_last_snapshot_and_never_calls_waha_synchronously(): void
    {
        DB::table('TChat')->where('Id', 'chat-group-1')->update([
            'IdWahaTerdeteksi' => '120363000000000000@g.us',
            'UrlFotoProfil' => 'https://waha.test/profiles/group-last.jpg',
            'TglFotoProfilDiambil' => now()->subDays(2),
        ]);

        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldNotReceive('getContactProfilePictureUrl');
        $this->app->instance(WahaSender::class, $wahaSender);

        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->call('selectChat', 'chat-group-1')
            ->assertStatus(200);

        self::assertSame(
            'https://waha.test/profiles/group-last.jpg',
            DB::table('TChat')->where('Id', 'chat-group-1')->value('UrlFotoProfil')
        );
        self::assertSame(
            '120363999999999999@g.us',
            $component->getData()['selectedChat']['Identity']['whatsapp']['GroupId']
        );
    }

    public function test_group_message_without_participant_avatar_uses_initial_fallback(): void
    {
        $this->insertChatDetail([
            'Id' => 'message-no-participant-avatar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => 'Pesan dari peserta tanpa foto.',
            'PengirimNamaKontak' => 'Zed Participant',
            'PengirimNomorWhatsapp' => '628999999999',
            'PengirimIdWaha' => '628999999999@c.us',
            'UrlFotoProfilPengirim' => null,
            'TglFotoProfilPengirimDiambil' => null,
        ]);

        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class);
        $message = collect($component->getData()['messages'] ?? [])->firstWhere('Id', 'message-no-participant-avatar');

        self::assertIsArray($message);
        self::assertSame('Zed Participant', $message['SenderName']);
        self::assertNull($message['SenderAvatarUrl']);
        $component->assertSee('Zed Participant')
            ->assertSee('Z');
    }

    public function test_personal_identity_switches_between_raw_and_mapped_values_without_resetting_selection_filter_or_messages(): void
    {
        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->set('filterType', 'pribadi');
        $component->assertStatus(200);

        $stateBeforeToggle = $component->getData();
        $chatRowsBeforeToggle = $stateBeforeToggle['chatRows'] ?? null;
        $messagesBeforeToggle = $stateBeforeToggle['messages'] ?? null;

        self::assertIsArray($chatRowsBeforeToggle);
        self::assertCount(1, $chatRowsBeforeToggle);
        self::assertArrayHasKey('Identity', $chatRowsBeforeToggle[0]);
        self::assertSame('WAHA Personal Contact', $chatRowsBeforeToggle[0]['Identity']['whatsapp']['ContactName']);
        self::assertSame('628333333333@c.us', $chatRowsBeforeToggle[0]['Identity']['whatsapp']['ChatId']);
        self::assertSame('Mapped Personal Contact', $chatRowsBeforeToggle[0]['Identity']['internal']['ContactName']);
        self::assertSame('Mapped Clinic', $chatRowsBeforeToggle[0]['Identity']['internal']['Instansi']);
        self::assertSame('628444444444', $chatRowsBeforeToggle[0]['Identity']['internal']['ContactNumber']);
        foreach ($messagesBeforeToggle as $message) {
            self::assertNull($message['MediaUrl']);
            self::assertNull($message['MediaDownloadUrl']);
        }

        $component->set('identityDisplayMode', 'internal')
            ->assertSet('identityDisplayMode', 'internal')
            ->assertSet('filterType', 'pribadi')
            ->assertSet('selectedChatId', 'chat-personal-1')
            ->assertSet('messages', $messagesBeforeToggle);
    }

    public function test_filters_unread_reply_internal_note_and_close_conversation_still_work(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $state = $component->getData();

        self::assertSame(1, $state['stats']['belum_dibaca']);
        self::assertCount(2, $state['chatRows']);

        $component->set('filterType', 'grup');
        self::assertCount(1, $component->getData()['chatRows']);

        $component->set('filterType', 'pribadi');
        self::assertCount(1, $component->getData()['chatRows']);

        $wahaSender = \Mockery::mock(WahaSender::class);
        $wahaSender->shouldReceive('sendText')
            ->once()
            ->with('default', '628333333333@c.us', 'Handled reply', 'WAHA_MANUAL_SEND_TEXT')
            ->andReturn(['ok' => true]);
        $this->app->instance(WahaSender::class, $wahaSender);

        Livewire::actingAs($this->agent([
            AccessPermissions::INBOX_VIEW,
            AccessPermissions::INBOX_REPLY,
            AccessPermissions::INBOX_MANAGE,
        ]))
            ->test(InboxWhatsapp::class)
            ->set('filterType', 'pribadi')
            ->set('replyText', 'Handled reply')
            ->call('kirimBalasanWaha')
            ->set('newInternalNote', 'Internal follow up')
            ->call('saveInternalNote');

        self::assertDatabaseHas('TChatD', [
            'IdChat' => 'chat-personal-1',
            'ArahPesan' => 'Keluar',
            'JenisPesan' => 'Teks',
            'IsiPesan' => 'Handled reply',
            'StatusKirim' => 'Terkirim WAHA',
        ]);
        self::assertDatabaseHas('TChatDCatatanInternal', [
            'IdChat' => 'chat-personal-1',
            'IsiCatatan' => 'Internal follow up',
        ]);
        self::assertSame(0, (int) DB::table('TChat')->where('Id', 'chat-personal-1')->value('JumlahPesanBelumDibaca'));

        $aiService = \Mockery::mock(AiAutoReplyService::class);
        $aiService->shouldReceive('sendClosingMessage')->once()->with('chat-personal-1');
        $this->app->instance(AiAutoReplyService::class, $aiService);

        Livewire::actingAs($this->agent([
            AccessPermissions::INBOX_VIEW,
            AccessPermissions::INBOX_MANAGE,
        ]))
            ->test(InboxWhatsapp::class)
            ->set('filterType', 'pribadi')
            ->call('tutupPercakapan');

        self::assertSame('status-closed', DB::table('TChat')->where('Id', 'chat-personal-1')->value('IdStatusChat'));
    }

    public function test_mapped_group_with_non_jid_number_does_not_use_number_as_internal_group_id(): void
    {
        DB::table('MGrupWhatsapp')->where('Id', 'group-map-1')->update([
            'IdGrupWaha' => null,
            'NomorGrupWhatsapp' => '628123456789',
        ]);

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $selectedChat = $state['selectedChat'] ?? null;

        self::assertIsArray($selectedChat);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['internal']['GroupId']);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['internal']['ChatId']);
        self::assertNotSame('628123456789', $selectedChat['Identity']['internal']['GroupId']);
        self::assertNotSame('628123456789', $selectedChat['Identity']['internal']['ChatId']);
    }

    public function test_legacy_url_media_with_complete_metadata_keeps_media_routes_without_payload_inspection(): void
    {
        DB::table('TChatD')->insert([
            'Id' => 'message-legacy-url',
            'IdChat' => 'chat-group-1',
            'ArahPesan' => 'Masuk',
            'JenisPesan' => 'Dokumen',
            'IsiPesan' => null,
            'UrlMedia' => '/api/files/legacy.pdf',
            'PayloadJson' => json_encode([
                'hasMedia' => true,
                'media' => [
                    'data' => base64_encode('embedded payload that must stay out of state'),
                    'mimetype' => 'image/png',
                    'filename' => 'embedded.png',
                ],
            ], JSON_THROW_ON_ERROR),
            'NamaFileMedia' => 'legacy.pdf',
            'TipeMime' => 'application/pdf',
            'PengirimNomorWhatsapp' => '628222222222',
            'PengirimNamaKontak' => 'Alice Raw',
            'TglPesan' => now()->addSecond(),
            'StatusKirim' => 'Diterima',
            'PesanError' => null,
            'DihasilkanOlehAi' => false,
            'DibalasOleh' => null,
            'TglBuat' => now(),
        ]);

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $messages = $state['messages'] ?? [];
        $legacyMessage = collect($messages)->firstWhere('Id', 'message-legacy-url');

        self::assertIsArray($legacyMessage);
        self::assertSame('pdf', $legacyMessage['MediaCategory']);
        self::assertSame('legacy.pdf', $legacyMessage['MediaLabel']);
        self::assertStringContainsString('/admin/waha-media/message-legacy-url', $legacyMessage['MediaUrl']);
        self::assertStringContainsString('download=1', $legacyMessage['MediaDownloadUrl']);
        self::assertStringNotContainsString('embedded payload', json_encode($messages, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('embedded.png', json_encode($messages, JSON_THROW_ON_ERROR));
    }

    public function test_data_uri_url_media_is_not_serialized_to_livewire_state(): void
    {
        $encoded = base64_encode('raw data uri media');
        $dataUri = 'data:image/png;base64,'.$encoded;

        $this->insertChatDetail([
            'Id' => 'message-data-uri-url',
            'JenisPesan' => 'Gambar',
            'UrlMedia' => $dataUri,
            'NamaFileMedia' => 'photo.png',
            'TipeMime' => 'image/png',
        ]);

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $messages = $state['messages'] ?? [];
        $message = collect($messages)->firstWhere('Id', 'message-data-uri-url');
        $stateJson = json_encode($messages, JSON_THROW_ON_ERROR);

        self::assertIsArray($message);
        self::assertNull($message['UrlMedia']);
        self::assertSame('image', $message['MediaCategory']);
        self::assertStringContainsString('/admin/waha-media/message-data-uri-url', $message['MediaUrl']);
        self::assertStringContainsString('download=1', $message['MediaDownloadUrl']);
        self::assertStringNotContainsString($dataUri, $stateJson);
        self::assertStringNotContainsString($encoded, $stateJson);
    }

    public function test_raw_base64_message_renders_media_route_without_text_body(): void
    {
        $encoded = base64_encode(str_repeat('image-bytes-', 10));

        $this->insertChatDetail([
            'Id' => 'message-raw-base64',
            'JenisPesan' => 'Gambar',
            'IsiPesan' => $encoded,
            'UrlMedia' => null,
            'PayloadJson' => null,
            'NamaFileMedia' => 'photo.png',
            'TipeMime' => 'image/png',
        ]);

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $message = collect($state['messages'] ?? [])->firstWhere('Id', 'message-raw-base64');
        $stateJson = json_encode($state, JSON_THROW_ON_ERROR);

        self::assertIsArray($message);
        self::assertSame('image', $message['MediaCategory']);
        self::assertFalse($message['ShowTextBody']);
        self::assertFalse($message['Base64Fallback']);
        self::assertStringContainsString('/admin/waha-media/message-raw-base64', $message['MediaUrl']);
        self::assertStringNotContainsString($encoded, $stateJson);
    }

    public function test_base64_like_text_message_stays_text_when_not_media_context(): void
    {
        $encoded = base64_encode(str_repeat('ordinary-text-', 10));

        $this->insertChatDetail([
            'Id' => 'message-base64-like-text',
            'JenisPesan' => 'Teks',
            'IsiPesan' => $encoded,
            'UrlMedia' => null,
            'PayloadJson' => null,
            'NamaFileMedia' => null,
            'TipeMime' => null,
        ]);

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $message = collect($state['messages'] ?? [])->firstWhere('Id', 'message-base64-like-text');

        self::assertIsArray($message);
        self::assertSame('text', $message['MediaCategory']);
        self::assertTrue($message['ShowTextBody']);
        self::assertFalse($message['Base64Fallback']);
        self::assertNull($message['MediaUrl']);
        self::assertSame($encoded, $message['IsiPesan']);
    }

    public function test_malformed_embedded_base64_does_not_create_media_routes_in_inbox_state(): void
    {
        $payload = json_encode([
            'hasMedia' => true,
            'media' => [
                'data' => '%%%not-base64%%%',
                'mimetype' => 'application/pdf',
                'filename' => 'broken.pdf',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->insertChatDetail([
            'Id' => 'message-malformed-embedded',
            'JenisPesan' => 'Dokumen',
            'UrlMedia' => null,
            'PayloadJson' => $payload,
            'NamaFileMedia' => 'broken.pdf',
            'TipeMime' => 'application/pdf',
        ]);

        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class);
        $state = $component->getData();
        $messages = $state['messages'] ?? [];
        $message = collect($messages)->firstWhere('Id', 'message-malformed-embedded');
        $stateJson = json_encode($messages, JSON_THROW_ON_ERROR);

        self::assertIsArray($message);
        self::assertNull($message['MediaUrl']);
        self::assertNull($message['MediaDownloadUrl']);
        self::assertTrue($message['Base64Fallback']);
        $component->assertSee(__('ui.pages.inbox.base64_media_unavailable'));
        self::assertStringNotContainsString('%%%not-base64%%%', $stateJson);
        self::assertStringNotContainsString($payload, $stateJson);
    }

    public function test_url_media_preview_category_uses_safe_inline_allowlist(): void
    {
        $this->insertChatDetail([
            'Id' => 'message-image-preview',
            'JenisPesan' => 'Gambar',
            'UrlMedia' => '/api/files/photo.png',
            'NamaFileMedia' => 'photo.png',
            'TipeMime' => 'image/png',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-video-preview',
            'JenisPesan' => 'Video',
            'UrlMedia' => '/api/files/video.mp4',
            'NamaFileMedia' => 'video.mp4',
            'TipeMime' => 'video/mp4',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-audio-preview',
            'JenisPesan' => 'Audio',
            'UrlMedia' => '/api/files/audio.ogg',
            'NamaFileMedia' => 'audio.ogg',
            'TipeMime' => 'audio/ogg',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-pdf-preview',
            'JenisPesan' => 'Dokumen',
            'UrlMedia' => '/api/files/report.pdf',
            'NamaFileMedia' => 'report.pdf',
            'TipeMime' => 'application/pdf',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-svg-file-card',
            'JenisPesan' => 'Gambar',
            'UrlMedia' => '/api/files/active.svg',
            'NamaFileMedia' => 'active.svg',
            'TipeMime' => 'image/svg+xml',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-html-file-card',
            'JenisPesan' => 'Dokumen',
            'UrlMedia' => '/api/files/active.html',
            'NamaFileMedia' => 'active.html',
            'TipeMime' => 'text/html',
        ]);
        $this->insertChatDetail([
            'Id' => 'message-unknown-file-card',
            'JenisPesan' => 'File',
            'UrlMedia' => '/api/files/blob.bin',
            'NamaFileMedia' => 'blob.bin',
            'TipeMime' => 'application/octet-stream',
        ]);

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $messages = collect($component->getData()['messages'] ?? [])->keyBy('Id');

        self::assertSame('image', $messages['message-image-preview']['MediaCategory']);
        self::assertSame('video', $messages['message-video-preview']['MediaCategory']);
        self::assertSame('audio', $messages['message-audio-preview']['MediaCategory']);
        self::assertSame('pdf', $messages['message-pdf-preview']['MediaCategory']);
        self::assertSame('file', $messages['message-svg-file-card']['MediaCategory']);
        self::assertSame('file', $messages['message-html-file-card']['MediaCategory']);
        self::assertSame('file', $messages['message-unknown-file-card']['MediaCategory']);

        $component
            ->assertSeeHtml('<video controls')
            ->assertSeeHtml('<audio controls')
            ->assertSeeHtml('<object')
            ->assertSee('admin/waha-media/message-pdf-preview', false)
            ->assertDontSeeHtml('<img src="http://localhost/admin/waha-media/message-svg-file-card"')
            ->assertSee('active.svg')
            ->assertSee('active.html')
            ->assertSee('blob.bin');
    }

    private function createSchema(): void
    {
        Schema::create('MInstansi', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaInstansi');
        });

        Schema::create('MCustomer', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaCustomer');
        });

        Schema::create('MNomorWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdCustomer')->nullable();
            $table->string('IdInstansi')->nullable();
            $table->string('NamaKontak')->nullable();
            $table->string('NomorWhatsapp');
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('MGrupWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdInstansi')->nullable();
            $table->string('NamaGrup')->nullable();
            $table->string('IdGrupWaha')->nullable();
            $table->string('NomorGrupWhatsapp')->nullable();
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('MStatusChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeStatusChat');
            $table->string('NamaStatusChat');
        });

        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi');
        });

        Schema::create('MPengguna', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaPengguna');
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdStatusChat')->nullable();
            $table->string('IdCustomer')->nullable();
            $table->string('IdInstansi')->nullable();
            $table->string('IdSesiWhatsapp')->nullable();
            $table->string('IdNomorWhatsapp')->nullable();
            $table->string('IdGrupWhatsapp')->nullable();
            $table->string('JenisChat');
            $table->string('NomorWhatsapp');
            $table->string('IdWahaTerdeteksi', 200)->nullable();
            $table->string('NomorWhatsappTerdeteksi')->nullable();
            $table->string('NamaKontak')->nullable();
            $table->string('NamaGrupWhatsapp')->nullable();
            $table->string('NamaKontakWaha', 150)->nullable();
            $table->string('NamaGrupWaha', 200)->nullable();
            $table->dateTime('TglIdentitasWahaDiambil')->nullable();
            $table->string('StatusIdentitasWaha', 30)->nullable();
            $table->string('PesanErrorIdentitasWaha', 500)->nullable();
            $table->string('UrlFotoProfil', 1000)->nullable();
            $table->dateTime('TglFotoProfilDiambil')->nullable();
            $table->integer('JumlahPesanBelumDibaca')->default(0);
            $table->dateTime('TglChatTerakhir')->nullable();
            $table->boolean('AutoReplyAiAktif')->default(false);
            $table->boolean('AiSudahMenyapa')->default(false);
            $table->dateTime('TglAutoReplyAiTerakhir')->nullable();
            $table->dateTime('TglDibalasTerakhir')->nullable();
            $table->string('DiambilOleh')->nullable();
            $table->dateTime('TglBuat');
            $table->dateTime('TglEdit')->nullable();
        });

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->string('ArahPesan');
            $table->string('JenisPesan');
            $table->text('IsiPesan')->nullable();
            $table->string('UrlMedia')->nullable();
            $table->text('PayloadJson')->nullable();
            $table->string('NamaFileMedia')->nullable();
            $table->string('TipeMime')->nullable();
            $table->boolean('DikirimOlehCustomer')->default(false);
            $table->dateTime('TglDikirim')->nullable();
            $table->string('PengirimNomorWhatsapp')->nullable();
            $table->string('PengirimNamaKontak')->nullable();
            $table->string('PengirimIdWaha', 200)->nullable();
            $table->string('UrlFotoProfilPengirim', 1000)->nullable();
            $table->dateTime('TglFotoProfilPengirimDiambil')->nullable();
            $table->dateTime('TglPesan');
            $table->string('StatusKirim')->nullable();
            $table->text('PesanError')->nullable();
            $table->boolean('DihasilkanOlehAi')->default(false);
            $table->string('DibalasOleh')->nullable();
            $table->dateTime('TglBuat');
        });

        Schema::create('TChatDCatatanInternal', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->text('IsiCatatan');
            $table->string('DibuatOleh')->nullable();
            $table->dateTime('TglBuat');
        });
    }

    private function seedFixtures(): void
    {
        $now = now();

        DB::table('MInstansi')->insert([
            ['Id' => 'instansi-group-1', 'NamaInstansi' => 'Group Clinic'],
            ['Id' => 'instansi-personal-1', 'NamaInstansi' => 'Mapped Clinic'],
        ]);
        DB::table('MCustomer')->insert(['Id' => 'customer-personal-1', 'NamaCustomer' => 'Mapped Customer']);
        DB::table('MNomorWhatsapp')->insert([
            'Id' => 'number-personal-1',
            'IdCustomer' => 'customer-personal-1',
            'IdInstansi' => 'instansi-personal-1',
            'NamaKontak' => 'Mapped Personal Contact',
            'NomorWhatsapp' => '628444444444',
            'NonAktif' => false,
        ]);
        DB::table('MGrupWhatsapp')->insert([
            'Id' => 'group-map-1',
            'IdInstansi' => 'instansi-group-1',
            'NamaGrup' => 'Internal Support Group',
            'IdGrupWaha' => '120363000000000000@g.us',
            'NomorGrupWhatsapp' => '120363000000000000',
            'NonAktif' => false,
        ]);
        DB::table('MStatusChat')->insert([
            ['Id' => 'status-open', 'KodeStatusChat' => 'BUKA', 'NamaStatusChat' => 'Open'],
            ['Id' => 'status-closed', 'KodeStatusChat' => 'DITUTUP', 'NamaStatusChat' => 'Closed'],
        ]);
        DB::table('MSesiWhatsapp')->insert(['Id' => 'session-1', 'KodeSesi' => 'default']);
        DB::table('MPengguna')->insert(['Id' => 'agent-1', 'NamaPengguna' => 'Agent Test']);
        DB::table('TChat')->insert([
            [
                'Id' => 'chat-group-1',
                'IdStatusChat' => 'status-open',
                'IdCustomer' => null,
                'IdInstansi' => null,
                'IdSesiWhatsapp' => 'session-1',
                'IdNomorWhatsapp' => null,
                'IdGrupWhatsapp' => 'group-map-1',
                'JenisChat' => 'Grup',
                'NomorWhatsapp' => '628111111111',
                'NamaKontak' => null,
                'NamaGrupWhatsapp' => 'Fallback Group',
                'NamaKontakWaha' => null,
                'NamaGrupWaha' => 'WAHA Support Group',
                'TglIdentitasWahaDiambil' => $now,
                'StatusIdentitasWaha' => 'synced',
                'PesanErrorIdentitasWaha' => null,
                'JumlahPesanBelumDibaca' => 1,
                'TglChatTerakhir' => $now,
                'AutoReplyAiAktif' => false,
                'AiSudahMenyapa' => false,
                'TglAutoReplyAiTerakhir' => null,
                'TglDibalasTerakhir' => null,
                'DiambilOleh' => 'agent-existing',
                'TglBuat' => $now,
                'TglEdit' => null,
            ],
            [
                'Id' => 'chat-personal-1',
                'IdStatusChat' => 'status-open',
                'IdCustomer' => 'customer-personal-1',
                'IdInstansi' => 'instansi-personal-1',
                'IdSesiWhatsapp' => 'session-1',
                'IdNomorWhatsapp' => 'number-personal-1',
                'IdGrupWhatsapp' => null,
                'JenisChat' => 'Pribadi',
                'NomorWhatsapp' => '628333333333',
                'NamaKontak' => 'Raw Personal Contact',
                'NamaGrupWhatsapp' => null,
                'NamaKontakWaha' => 'WAHA Personal Contact',
                'NamaGrupWaha' => null,
                'TglIdentitasWahaDiambil' => $now,
                'StatusIdentitasWaha' => 'synced',
                'PesanErrorIdentitasWaha' => null,
                'JumlahPesanBelumDibaca' => 0,
                'TglChatTerakhir' => $now->copy()->subMinute(),
                'AutoReplyAiAktif' => false,
                'AiSudahMenyapa' => false,
                'TglAutoReplyAiTerakhir' => null,
                'TglDibalasTerakhir' => null,
                'DiambilOleh' => 'agent-existing',
                'TglBuat' => $now,
                'TglEdit' => null,
            ],
        ]);

        DB::table('TChatD')->insert([
            [
                'Id' => 'message-group-1',
                'IdChat' => 'chat-group-1',
                'ArahPesan' => 'Masuk',
                'JenisPesan' => 'Dokumen',
                'IsiPesan' => null,
                'UrlMedia' => null,
                'PayloadJson' => json_encode([
                    'chatId' => '120363999999999999@g.us',
                    'participant' => '628222222222@c.us',
                    'sender' => ['pushname' => 'Alice Raw'],
                    'hasMedia' => true,
                    'media' => [
                        'data' => base64_encode("%PDF-1.4\n%%EOF"),
                        'mimetype' => 'application/pdf',
                        'filename' => 'group-document.pdf',
                    ],
                ], JSON_THROW_ON_ERROR),
                'PengirimNomorWhatsapp' => '628222222222',
                'PengirimNamaKontak' => 'Alice Raw',
                'PengirimIdWaha' => '628222222222@c.us',
                'UrlFotoProfilPengirim' => 'https://waha.test/profiles/alice.jpg',
                'TglFotoProfilPengirimDiambil' => $now,
                'TglPesan' => $now,
                'StatusKirim' => 'Diterima',
                'PesanError' => null,
                'DihasilkanOlehAi' => false,
                'DibalasOleh' => null,
                'TglBuat' => $now,
            ],
            [
                'Id' => 'message-personal-1',
                'IdChat' => 'chat-personal-1',
                'ArahPesan' => 'Masuk',
                'JenisPesan' => 'Teks',
                'IsiPesan' => 'First personal message',
                'UrlMedia' => null,
                'PayloadJson' => json_encode(['chatId' => '628333333333@c.us'], JSON_THROW_ON_ERROR),
                'PengirimNomorWhatsapp' => '628333333333',
                'PengirimNamaKontak' => 'Raw Personal Contact',
                'PengirimIdWaha' => '628333333333@c.us',
                'UrlFotoProfilPengirim' => null,
                'TglFotoProfilPengirimDiambil' => null,
                'TglPesan' => $now->copy()->subMinutes(2),
                'StatusKirim' => 'Diterima',
                'PesanError' => null,
                'DihasilkanOlehAi' => false,
                'DibalasOleh' => null,
                'TglBuat' => $now,
            ],
            [
                'Id' => 'message-personal-2',
                'IdChat' => 'chat-personal-1',
                'ArahPesan' => 'Keluar',
                'JenisPesan' => 'Teks',
                'IsiPesan' => 'Second personal message',
                'UrlMedia' => null,
                'PayloadJson' => null,
                'PengirimNomorWhatsapp' => null,
                'PengirimNamaKontak' => null,
                'PengirimIdWaha' => null,
                'UrlFotoProfilPengirim' => null,
                'TglFotoProfilPengirimDiambil' => null,
                'TglPesan' => $now->copy()->subMinute(),
                'StatusKirim' => 'Terkirim',
                'PesanError' => null,
                'DihasilkanOlehAi' => false,
                'DibalasOleh' => 'agent-1',
                'TglBuat' => $now,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertChatDetail(array $attributes): void
    {
        DB::table('TChatD')->insert([
            'IdChat' => 'chat-group-1',
            'ArahPesan' => 'Masuk',
            'JenisPesan' => 'Teks',
            'IsiPesan' => null,
            'UrlMedia' => null,
            'PayloadJson' => null,
            'NamaFileMedia' => null,
            'TipeMime' => null,
            'PengirimNomorWhatsapp' => '628222222222',
            'PengirimNamaKontak' => 'Alice Raw',
            'TglPesan' => now()->addSeconds(10 + DB::table('TChatD')->count()),
            'StatusKirim' => 'Diterima',
            'PesanError' => null,
            'DihasilkanOlehAi' => false,
            'DibalasOleh' => null,
            'TglBuat' => now(),
            ...$attributes,
        ]);
    }

    /** @param array<int, string> $permissions */
    private function agent(array $permissions = [AccessPermissions::INBOX_VIEW]): Pengguna
    {
        $agent = new class extends Pengguna
        {
            /** @var array<int, string> */
            public array $testPermissions = [];

            public function hasPermissionCode(string $permission): bool
            {
                return in_array($permission, $this->testPermissions, true);
            }
        };

        $agent->testPermissions = $permissions;

        return $agent->forceFill([
            'Id' => 'agent-1',
            'NamaPengguna' => 'Agent Test',
            'NonAktif' => false,
        ]);
    }
}
