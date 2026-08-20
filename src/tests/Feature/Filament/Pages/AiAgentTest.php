<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\AiAgent;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AiAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->name('ai-agent-test.livewire.update');
        });
        app('router')->getRoutes()->refreshNameLookups();

        $this->createSchema();
        $this->seedSettings();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TAiPermintaan');
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('MPengaturanAi');
        Schema::dropIfExists('MPengguna');
        Cache::flush();

        parent::tearDown();
    }

    public function test_ai_agent_persists_whatsapp_refinement_default(): void
    {
        Cache::put('mpengaturan_ai_default_v2', ['stale' => true]);

        $component = Livewire::actingAs($this->agent())
            ->test(AiAgent::class)
            ->assertSet('pengaturan.PerhalusJawabanWhatsappDefault', false)
            ->assertSee(__('ui.pages.ai_agent.refine_whatsapp_replies'))
            ->assertSeeHtml('pengaturan.PerhalusJawabanWhatsappDefault')
            ->set('pengaturan.PerhalusJawabanWhatsappDefault', 'invalid')
            ->call('simpanPengaturan')
            ->assertHasErrors(['pengaturan.PerhalusJawabanWhatsappDefault' => 'boolean']);

        $component
            ->set('pengaturan.PerhalusJawabanWhatsappDefault', true)
            ->call('simpanPengaturan')
            ->assertHasNoErrors();

        self::assertSame(1, (int) DB::table('MPengaturanAi')->value('PerhalusJawabanWhatsappDefault'));
        self::assertFalse(Cache::has('mpengaturan_ai_default_v2'));
    }

    public function test_operator_refinement_override_has_follow_active_inactive_states(): void
    {
        DB::table('MPengguna')->insert([
            ['Id' => 'operator-follow', 'NamaPengguna' => 'Follow', 'PerhalusJawabanWhatsapp' => null],
            ['Id' => 'operator-active', 'NamaPengguna' => 'Active', 'PerhalusJawabanWhatsapp' => true],
            ['Id' => 'operator-inactive', 'NamaPengguna' => 'Inactive', 'PerhalusJawabanWhatsapp' => false],
        ]);

        self::assertNull(DB::table('MPengguna')->where('Id', 'operator-follow')->value('PerhalusJawabanWhatsapp'));
        self::assertSame(1, (int) DB::table('MPengguna')->where('Id', 'operator-active')->value('PerhalusJawabanWhatsapp'));
        self::assertSame(0, (int) DB::table('MPengguna')->where('Id', 'operator-inactive')->value('PerhalusJawabanWhatsapp'));
    }

    public function test_ai_agent_persists_ai_signature(): void
    {
        Livewire::actingAs($this->agent())
            ->test(AiAgent::class)
            ->assertSee(__('ui.pages.ai_agent.ai_signature'))
            ->set('pengaturan.TandaTanganAi', '~ Auto Reply by VICA')
            ->call('simpanPengaturan')
            ->assertHasNoErrors();

        self::assertSame('~ Auto Reply by VICA', DB::table('MPengaturanAi')->value('TandaTanganAi'));
    }

    public function test_ai_signature_setting_is_ignored_before_migration(): void
    {
        Schema::table('MPengaturanAi', function (Blueprint $table): void {
            $table->dropColumn('TandaTanganAi');
        });

        Livewire::actingAs($this->agent())
            ->test(AiAgent::class)
            ->assertSet('pengaturan.TandaTanganAi', null)
            ->set('pengaturan.TandaTanganAi', '~ Auto Reply by VICA')
            ->call('simpanPengaturan')
            ->assertHasNoErrors();

        self::assertArrayNotHasKey('TandaTanganAi', (array) DB::table('MPengaturanAi')->first());
    }

    public function test_fallback_when_whatsapp_refinement_schema_is_missing(): void
    {
        Schema::table('MPengaturanAi', function (Blueprint $table): void {
            $table->dropColumn('PerhalusJawabanWhatsappDefault');
        });
        $component = Livewire::actingAs($this->agent())->test(AiAgent::class);
        $component->assertSet('pengaturan.PerhalusJawabanWhatsappDefault', false);
        $component->set('pengaturan.PerhalusJawabanWhatsappDefault', true)->call('simpanPengaturan')->assertHasNoErrors();
        self::assertArrayNotHasKey('PerhalusJawabanWhatsappDefault', (array) DB::table('MPengaturanAi')->first());
    }

    private function createSchema(): void
    {
        Schema::create('MPengguna', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaPengguna');
            $table->string('NomorWhatsappInternal')->nullable();
            $table->boolean('NonAktif')->default(false);
            $table->boolean('PerhalusJawabanWhatsapp')->nullable();
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->boolean('AutoReplyAiAktif')->default(false);
        });

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->boolean('DihasilkanOlehAi')->default(false);
        });

        Schema::create('TAiPermintaan', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->dateTime('TglBuat')->nullable();
        });

        Schema::create('MPengaturanAi', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodePengaturan')->unique();
            $table->string('NamaPengaturan');
            $table->boolean('AutoReplyAktif')->default(false);
            $table->boolean('AutoReplyDiluarJamKerja')->default(true);
            $table->boolean('AutoReplyHariLibur')->default(true);
            $table->boolean('AutoReplyJamKerjaSapaan')->default(true);
            $table->boolean('AutoReplyJamKerjaBerlanjut')->default(false);
            $table->string('JamKerjaMulai')->default('08:00');
            $table->string('JamKerjaSelesai')->default('17:00');
            $table->string('HariKerja')->default('1,2,3,4,5');
            $table->string('ZonaWaktu')->default('Asia/Jakarta');
            $table->string('ProviderAi')->default('OpenAI');
            $table->string('ModelAi')->nullable();
            $table->string('ModelInstructAi')->nullable();
            $table->string('BaseUrl')->nullable();
            $table->text('PromptSistem')->nullable();
            $table->text('TemplateDiluarJamKerja')->nullable();
            $table->text('TemplateHariLibur')->nullable();
            $table->text('TemplateJamKerjaSapaan')->nullable();
            $table->text('TemplateFallback')->nullable();
            $table->boolean('NotifikasiChatBelumTerbalasAktif')->default(true);
            $table->integer('MenitTungguNotifikasi')->default(10);
            $table->integer('JedaNotifikasiMenit')->default(30);
            $table->string('KodePeranPenerimaNotifikasi')->default('ADMIN,SUPERVISOR_CS,CS');
            $table->text('TemplateNotifikasiChatBelumTerbalas')->nullable();
            $table->text('ExcludeNomorWhatsapp')->nullable();
            $table->integer('BatasRiwayatPesan')->default(8);
            $table->boolean('KirimKeWaha')->default(false);
            $table->string('ModeKirim')->default('DraftLokal');
            $table->boolean('NonAktif')->default(false);
            $table->dateTime('TglBuat')->nullable();
            $table->dateTime('TglEdit')->nullable();
            $table->string('TandaTanganAi')->nullable();
            $table->boolean('PerhalusJawabanWhatsappDefault')->default(false);
        });
    }

    private function seedSettings(): void
    {
        DB::table('MPengaturanAi')->insert([
            'Id' => 'ai-settings-default',
            'KodePengaturan' => 'DEFAULT',
            'NamaPengaturan' => 'Pengaturan Default AI Agent',
            'AutoReplyAktif' => false,
            'AutoReplyDiluarJamKerja' => true,
            'AutoReplyHariLibur' => true,
            'AutoReplyJamKerjaSapaan' => true,
            'AutoReplyJamKerjaBerlanjut' => false,
            'JamKerjaMulai' => '08:00',
            'JamKerjaSelesai' => '17:00',
            'HariKerja' => '1,2,3,4,5',
            'ZonaWaktu' => 'Asia/Jakarta',
            'ProviderAi' => 'OpenAI',
            'ModelAi' => 'gpt-5',
            'BaseUrl' => 'https://api.openai.com/v1/responses',
            'PromptSistem' => 'Prompt test',
            'TemplateDiluarJamKerja' => 'After hours',
            'TemplateHariLibur' => 'Holiday',
            'TemplateJamKerjaSapaan' => 'Greeting',
            'TemplateFallback' => 'Fallback',
            'NotifikasiChatBelumTerbalasAktif' => true,
            'MenitTungguNotifikasi' => 10,
            'JedaNotifikasiMenit' => 30,
            'KodePeranPenerimaNotifikasi' => 'ADMIN',
            'TemplateNotifikasiChatBelumTerbalas' => 'Notification',
            'ExcludeNomorWhatsapp' => '',
            'BatasRiwayatPesan' => 8,
            'KirimKeWaha' => false,
            'ModeKirim' => 'DraftLokal',
            'NonAktif' => false,
            'PerhalusJawabanWhatsappDefault' => false,
        ]);
    }

    private function agent(): Pengguna
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

        $agent->testPermissions = [
            AccessPermissions::AI_AGENT_VIEW,
            AccessPermissions::AI_AGENT_MANAGE,
        ];

        return $agent->forceFill([
            'Id' => 'agent-1',
            'NamaPengguna' => 'Agent Test',
            'NonAktif' => false,
        ]);
    }
}
