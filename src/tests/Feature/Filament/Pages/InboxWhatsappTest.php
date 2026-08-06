<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\InboxWhatsapp;
use App\Models\Master\Pengguna;
use App\Services\Ai\AiAutoReplyService;
use App\Services\Waha\WahaSender;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        self::assertSame('Internal Support Group', $chatRows[0]['Identity']['internal']['GroupName']);
        self::assertIsArray($selectedChat);
        self::assertArrayHasKey('Identity', $selectedChat);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['whatsapp']['ChatId']);
        self::assertSame('Internal Support Group', $selectedChat['Identity']['whatsapp']['GroupName']);
        self::assertSame('120363999999999999@g.us', $selectedChat['Identity']['whatsapp']['GroupId']);
        self::assertSame('Internal Support Group', $selectedChat['Identity']['internal']['GroupName']);
        self::assertNotSame('628222222222@c.us', $selectedChat['Identity']['whatsapp']['ChatId']);
        self::assertNotSame('628222222222@c.us', $selectedChat['Identity']['whatsapp']['GroupId']);
        self::assertSame('Alice Raw', $messages[0]['SenderName']);
        self::assertSame('628222222222', $messages[0]['SenderNumber']);
        self::assertStringContainsString('/admin/waha-media/message-group-1', $messages[0]['MediaUrl']);
        self::assertStringContainsString('download=1', $messages[0]['MediaDownloadUrl']);
        self::assertStringNotContainsString($encodedMedia, json_encode([
            'messages' => $messages,
            'selectedChat' => $selectedChat,
            'chatRows' => $chatRows,
        ], JSON_THROW_ON_ERROR));
    }

    public function test_realtime_update_merges_messages_from_mapped_group_siblings(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);

        DB::table('TChat')->insert([
            'Id' => 'chat-group-mapped-sibling',
            'IdStatusChat' => 'status-open',
            'IdSesiWhatsapp' => 'session-1',
            'IdGrupWhatsapp' => 'group-map-1',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363999999999999@g.us',
            'IdWahaTerdeteksi' => '120363999999999999@g.us',
            'NamaGrupWhatsapp' => 'Fallback Group',
                'GroupName' => 'Internal Support Group',
            'JumlahPesanBelumDibaca' => 1,
            'TglChatTerakhir' => now()->addMinute(),
            'AutoReplyAiAktif' => false,
            'AiSudahMenyapa' => false,
            'DiambilOleh' => 'agent-existing',
            'TglBuat' => now(),
        ]);
        $this->insertChatDetail([
            'Id' => 'message-group-mapped-realtime',
            'IdChat' => 'chat-group-mapped-sibling',
            'IsiPesan' => 'Mapped group realtime message',
            'PayloadJson' => json_encode(['chatId' => '120363999999999999@g.us'], JSON_THROW_ON_ERROR),
        ]);

        $component->call('handleInboxUpdate', 'chat-group-mapped-sibling');

        $messages = collect($component->getData()['messages'] ?? []);

        self::assertTrue($messages->contains('Id', 'message-group-1'));
        self::assertTrue($messages->contains('Id', 'message-group-mapped-realtime'));
    }

    public function test_realtime_update_merges_messages_from_unmapped_group_siblings(): void
    {
        $groupJid = '120363888888888888@g.us';

        DB::table('TChat')->insert([
            'Id' => 'chat-group-unmapped-primary',
            'IdStatusChat' => 'status-open',
            'IdSesiWhatsapp' => 'session-1',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => $groupJid,
            'IdWahaTerdeteksi' => $groupJid,
            'NamaGrupWhatsapp' => 'Unmapped Group',
            'JumlahPesanBelumDibaca' => 0,
            'TglChatTerakhir' => now()->addMinutes(2),
            'AutoReplyAiAktif' => false,
            'AiSudahMenyapa' => false,
            'DiambilOleh' => 'agent-existing',
            'TglBuat' => now(),
        ]);
        $this->insertChatDetail([
            'Id' => 'message-group-unmapped-primary',
            'IdChat' => 'chat-group-unmapped-primary',
            'IsiPesan' => 'First unmapped group message',
            'PayloadJson' => json_encode(['chatId' => $groupJid], JSON_THROW_ON_ERROR),
        ]);

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $component->call('selectChat', 'chat-group-unmapped-primary');

        DB::table('TChat')->insert([
            'Id' => 'chat-group-unmapped-sibling',
            'IdStatusChat' => 'status-open',
            'IdSesiWhatsapp' => 'session-1',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => $groupJid,
            'IdWahaTerdeteksi' => $groupJid,
            'NamaGrupWhatsapp' => 'Unmapped Group',
            'JumlahPesanBelumDibaca' => 1,
            'TglChatTerakhir' => now()->addMinutes(3),
            'AutoReplyAiAktif' => false,
            'AiSudahMenyapa' => false,
            'DiambilOleh' => 'agent-existing',
            'TglBuat' => now(),
        ]);
        $this->insertChatDetail([
            'Id' => 'message-group-unmapped-realtime',
            'IdChat' => 'chat-group-unmapped-sibling',
            'IsiPesan' => 'Unmapped group realtime message',
            'PayloadJson' => json_encode(['chatId' => $groupJid], JSON_THROW_ON_ERROR),
        ]);

        $component->call('handleInboxUpdate', 'chat-group-unmapped-sibling');

        $messages = collect($component->getData()['messages'] ?? []);

        self::assertTrue($messages->contains('Id', 'message-group-unmapped-primary'));
        self::assertTrue($messages->contains('Id', 'message-group-unmapped-realtime'));
    }

    public function test_renders_localized_identity_controls_group_sender_and_media_download(): void
    {
        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->assertSee('WhatsApp asli')
            ->assertSee('Data internal')
            ->assertSee('Grup WhatsApp')
            ->assertSee('Chat Pribadi')
            ->assertSee('Unduh media')
            ->assertSee('120363999999999999@g.us')
            ->assertSee('Alice Raw')
            ->assertSee('628222222222')
            ->assertSeeHtml('wire:click="$set(\'identityDisplayMode\', \'whatsapp\')"')
            ->assertSeeHtml('wire:click="$set(\'identityDisplayMode\', \'internal\')"')
            ->assertSeeHtml('download=1');

        app()->setLocale('en');

        Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->assertSee('Original WhatsApp')
            ->assertSee('Internal data')
            ->assertSee('Download media');
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
        self::assertSame('Raw Personal Contact', $chatRowsBeforeToggle[0]['Identity']['whatsapp']['ContactName']);
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

        $state = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->getData();
        $messages = $state['messages'] ?? [];
        $message = collect($messages)->firstWhere('Id', 'message-malformed-embedded');
        $stateJson = json_encode($messages, JSON_THROW_ON_ERROR);

        self::assertIsArray($message);
        self::assertNull($message['MediaUrl']);
        self::assertNull($message['MediaDownloadUrl']);
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

    public function test_large_group_starts_with_latest_messages_and_loads_older_messages_without_duplicates(): void
    {
        $baseTime = now()->addMinute();

        for ($index = 1; $index <= 250; $index++) {
            $this->insertChatDetail([
                'Id' => 'message-large-group-'.$index,
                'IsiPesan' => 'Large group message '.$index,
                'JenisPesan' => $index === 150 ? 'Dokumen' : 'Teks',
                'PayloadJson' => json_encode($index === 150 ? [
                    'chatId' => '120363999999999999@g.us',
                    'hasMedia' => true,
                    'media' => [
                        'data' => base64_encode('%PDF-1.4\n%%EOF'),
                        'mimetype' => 'application/pdf',
                        'filename' => 'older-group-document.pdf',
                    ],
                ] : ['chatId' => '120363999999999999@g.us'], JSON_THROW_ON_ERROR),
                'TglPesan' => $baseTime->copy()->addSeconds($index),
            ]);
        }

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $messages = collect($component->getData()['messages'] ?? []);

        self::assertCount(100, $messages);
        self::assertSame('message-large-group-151', $messages->first()['Id']);
        self::assertSame('message-large-group-250', $messages->last()['Id']);
        self::assertTrue($messages->contains('Id', 'message-large-group-250'));
        self::assertTrue($component->getData()['hasOlderMessages']);

        $component->call('loadOlderMessages');
        $olderMessages = collect($component->getData()['messages'] ?? []);

        self::assertSame($olderMessages->unique('Id')->count(), $olderMessages->count());
        self::assertTrue($olderMessages->contains('Id', 'message-large-group-250'));
        self::assertTrue($olderMessages->contains('Id', 'message-large-group-150'));

        $olderMedia = $olderMessages->firstWhere('Id', 'message-large-group-150');
        self::assertSame('pdf', $olderMedia['MediaCategory']);
        self::assertSame('older-group-document.pdf', $olderMedia['MediaLabel']);
        self::assertStringContainsString('/admin/waha-media/message-large-group-150', $olderMedia['MediaUrl']);
        self::assertStringContainsString('download=1', $olderMedia['MediaDownloadUrl']);
    }

    public function test_inbox_list_loads_latest_payloads_for_fifty_rooms_with_set_based_queries(): void
    {
        $baseTime = now()->setDate(2040, 1, 1)->startOfDay();

        for ($roomNumber = 1; $roomNumber <= 50; $roomNumber++) {
            $chatId = 'chat-query-shape-'.$roomNumber;
            $phoneNumber = '628555000'.str_pad((string) $roomNumber, 3, '0', STR_PAD_LEFT);
            $messageTime = $baseTime->copy()->addSeconds($roomNumber);

            DB::table('TChat')->insert([
                'Id' => $chatId,
                'IdStatusChat' => 'status-open',
                'IdCustomer' => null,
                'IdInstansi' => null,
                'IdSesiWhatsapp' => 'session-1',
                'IdNomorWhatsapp' => null,
                'IdGrupWhatsapp' => null,
                'JenisChat' => 'Pribadi',
                'NomorWhatsapp' => $phoneNumber,
                'NamaKontak' => 'Query Shape '.$roomNumber,
                'NamaGrupWhatsapp' => null,
                'GroupName' => null,
                'IdWahaTerdeteksi' => null,
                'NomorWhatsappTerdeteksi' => null,
                'JumlahPesanBelumDibaca' => 0,
                'TglChatTerakhir' => $messageTime,
                'AutoReplyAiAktif' => false,
                'AiSudahMenyapa' => false,
                'TglAutoReplyAiTerakhir' => null,
                'TglDibalasTerakhir' => null,
                'DiambilOleh' => 'agent-existing',
                'TglBuat' => $baseTime,
                'TglEdit' => null,
            ]);

            $this->insertChatDetail([
                'Id' => 'message-query-shape-'.$roomNumber,
                'IdChat' => $chatId,
                'IsiPesan' => 'Latest payload '.$roomNumber,
                'PayloadJson' => json_encode([
                    'chatId' => $phoneNumber.'@c.us',
                    'id' => 'payload-query-shape-'.$roomNumber,
                ], JSON_THROW_ON_ERROR),
                'TglPesan' => $messageTime,
                'TglBuat' => $messageTime,
            ]);
        }

        $queries = [];

        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($this->agent());
        $component = app(InboxWhatsapp::class);
        $component->mount();
        $chatRows = $component->chatRows;

        $inboxListDetailQueries = collect($queries)
            ->filter(static fn (string $sql): bool => str_contains(strtolower($sql), 'tchatd')
                && str_contains(strtolower($sql), 'row_number() over'));
        $payloadSelect = 'select '.chr(34).'payloadjson'.chr(34).' from '.chr(34).'tchatd'.chr(34);
        $latestPayloadLookups = collect($queries)
            ->filter(static fn (string $sql): bool => str_contains(strtolower($sql), $payloadSelect)
                && str_contains(strtolower($sql), 'where')
                && str_contains(strtolower($sql), 'idchat')
                && str_contains(strtolower($sql), '= ?'));

        self::assertCount(50, $chatRows);
        self::assertSame('chat-query-shape-50', $chatRows[0]['Id']);
        self::assertLessThanOrEqual(1, $inboxListDetailQueries->count(), implode(PHP_EOL, $inboxListDetailQueries->all()));
        self::assertCount(1, $inboxListDetailQueries);
        self::assertLessThanOrEqual(1, $latestPayloadLookups->count(), implode(PHP_EOL, $latestPayloadLookups->all()));
    }

    public function test_group_inbox_render_does_not_call_waha_for_metadata(): void
    {
        Http::fake();

        Livewire::actingAs($this->agent())->test(InboxWhatsapp::class)->assertStatus(200);

        Http::assertNothingSent();
    }

    public function test_realtime_burst_keeps_legacy_group_messages_unique(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);

        DB::table('TChat')->insert([
            'Id' => 'chat-group-legacy-sibling',
            'IdStatusChat' => 'status-open',
            'IdSesiWhatsapp' => 'session-1',
            'JenisChat' => 'Grup',
            'NomorWhatsapp' => '120363999999999999@g.us',
            'IdWahaTerdeteksi' => '120363999999999999@g.us',
            'NamaGrupWhatsapp' => 'Fallback Group',
                'GroupName' => 'Internal Support Group',
            'JumlahPesanBelumDibaca' => 0,
            'TglChatTerakhir' => now()->addMinute(),
            'AutoReplyAiAktif' => false,
            'AiSudahMenyapa' => false,
            'DiambilOleh' => 'agent-existing',
            'TglBuat' => now(),
        ]);
        $this->insertChatDetail([
            'Id' => 'message-group-legacy-burst',
            'IdChat' => 'chat-group-legacy-sibling',
            'IsiPesan' => 'Legacy group burst message',
            'PayloadJson' => json_encode(['chatId' => '120363999999999999@g.us'], JSON_THROW_ON_ERROR),
        ]);

        $component->call('handleInboxUpdate', 'chat-group-legacy-sibling');
        $component->call('handleInboxUpdate', 'chat-group-legacy-sibling');

        $messages = collect($component->getData()['messages'] ?? []);
        self::assertTrue($messages->contains('Id', 'message-group-legacy-burst'));
        self::assertSame($messages->count(), $messages->unique('Id')->count());
    }

    public function test_realtime_private_chat_refreshes_only_the_active_private_room(): void
    {
        $component = Livewire::actingAs($this->agent())
            ->test(InboxWhatsapp::class)
            ->call('selectChat', 'chat-personal-1');

        $this->insertChatDetail([
            'Id' => 'message-personal-realtime',
            'IdChat' => 'chat-personal-1',
            'IsiPesan' => 'Private realtime message',
        ]);

        $component->call('handleInboxUpdate', 'chat-personal-1');

        $messages = collect($component->getData()['messages'] ?? []);
        self::assertTrue($messages->contains('Id', 'message-personal-realtime'));
        self::assertFalse($messages->contains('Id', 'message-group-1'));
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
            $table->string('NamaKontak')->nullable();
            $table->string('NamaGrupWhatsapp')->nullable();
            $table->string('GroupName')->nullable();
            $table->string('IdWahaTerdeteksi')->nullable();
            $table->string('NomorWhatsappTerdeteksi')->nullable();
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
                'GroupName' => 'Internal Support Group',
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
                'GroupName' => null,
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
    public function test_group_name_from_database_is_used_as_visible_title(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $selectedChat = $component->getData()['selectedChat'] ?? null;

        self::assertIsArray($selectedChat);
        self::assertSame('Internal Support Group', $selectedChat['Identity']['internal']['GroupName']);
        self::assertSame('Internal Support Group', $selectedChat['NamaGrupWhatsapp']);
    }

    public function test_unknown_group_is_shown_when_group_name_cache_is_empty(): void
    {
        DB::table('TChat')->where('Id', 'chat-group-1')->update([
            'NamaGrupWhatsapp' => '',
            'GroupName' => '',
            'IdGrupWhatsapp' => null,
            'IdWahaTerdeteksi' => '120363028059901162@g.us',
        ]);

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $selectedChat = $component->getData()['selectedChat'] ?? null;

        self::assertIsArray($selectedChat);
        self::assertNotSame('120363028059901162@g.us', $selectedChat['Identity']['whatsapp']['GroupName']);
        self::assertNotSame('120363028059901162@g.us', $selectedChat['NamaGrupWhatsapp']);
    }

    public function test_inbox_render_does_not_make_waha_http_request(): void
    {
        Http::fake();

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $component->assertStatus(200);

        Http::assertNothingSent();
    }

    public function test_sync_selected_group_name_dispatches_refresh_job(): void
    {
        Queue::fake();
        $component = Livewire::actingAs($this->agent([AccessPermissions::INBOX_VIEW, AccessPermissions::INBOX_MANAGE]))->test(InboxWhatsapp::class);

        $component->call('syncSelectedGroupName');

        $component->assertHasNoErrors();
    }

    public function test_sync_all_group_names_dispatches_jobs_for_empty_groups_only(): void
    {
        Queue::fake();
        $component = Livewire::actingAs($this->agent([AccessPermissions::INBOX_VIEW, AccessPermissions::INBOX_MANAGE]))->test(InboxWhatsapp::class);

        DB::table('TChat')->where('Id', 'chat-group-1')->update([
            'NamaGrupWhatsapp' => 'Has Name',
            'GroupName' => 'Has Name',
        ]);

        $component->call('syncMissingGroupNames');

        $component->assertHasNoErrors();
    }

    public function test_media_preview_and_download_still_render_for_group_messages(): void
    {
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $messages = $component->getData()['messages'] ?? [];

        $mediaMessage = collect($messages)->firstWhere('Id', 'message-group-1');

        self::assertNotNull($mediaMessage);
        self::assertNotEmpty($mediaMessage['MediaUrl']);
        self::assertNotEmpty($mediaMessage['MediaDownloadUrl']);
        self::assertStringContainsString('download=1', $mediaMessage['MediaDownloadUrl']);
    }

    public function test_base64_payload_is_not_exposed_in_rendered_html(): void
    {
        $encodedMedia = base64_encode("%PDF-1.4\n%%EOF");
        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $state = $component->getData();

        $jsonState = json_encode($state, JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString($encodedMedia, $jsonState);
    }

    public function test_group_jid_is_not_used_as_visible_title(): void
    {
        DB::table('TChat')->where('Id', 'chat-group-1')->update([
            'NamaGrupWhatsapp' => '120363028059901162@g.us',
            'GroupName' => null,
            'IdGrupWhatsapp' => null,
        ]);

        $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
        $selectedChat = $component->getData()['selectedChat'] ?? null;

        self::assertIsArray($selectedChat);
        self::assertNotSame('120363028059901162@g.us', $selectedChat['Identity']['whatsapp']['GroupName']);
    }

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
