<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\WahaConnectionCenter;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WahaConnectionCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->name('waha-connection-center-test.livewire.update');
        });
        app('router')->getRoutes()->refreshNameLookups();
        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        foreach ([
            'MSesiWhatsapp',
            'MPengguna',
            'TLogAktivitas',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Cache::flush();

        parent::tearDown();
    }

    public function test_user_without_view_permission_cannot_access_connection_center(): void
    {
        $user = $this->user([]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_access_and_render_sessions(): void
    {
        Http::fake([
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
                'me' => ['id' => '628123456789@c.us'],
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->assertStatus(200)
            ->assertSee('WAHA Connection Center')
            ->assertSee('default')
            ->assertSee('BERJALAN');
    }

    public function test_operator_with_manage_permission_can_trigger_qr_modal_and_fetch_qr(): void
    {
        Http::fake([
            '*/api/default/auth/qr' => Http::response([
                'qr' => 'data:image/png;base64,TEST_QR_DATA',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openQrModal', 'default', 'Session Default')
            ->assertSet('activeModalSession', 'default')
            ->assertSet('qrCodePayload', 'data:image/png;base64,TEST_QR_DATA');
    }

    public function test_operator_can_submit_pairing_code(): void
    {
        Http::fake([
            '*/api/default/auth/request-code' => Http::response([
                'code' => '1234-5678',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('openPairingModal', 'default', 'Session Default')
            ->set('pairingPhoneNumber', '628123456789')
            ->call('submitPairingCode')
            ->assertSet('pairingCodePayload', '1234-5678');
    }

    public function test_operator_can_execute_start_session_action(): void
    {
        Http::fake([
            '*/api/sessions/start' => Http::response([
                'name' => 'default',
                'status' => 'STARTING',
            ], 200),
            '*/api/sessions/default' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW, AccessPermissions::WAHA_SESSION_MANAGE]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('startSession', 'default')
            ->assertHasNoErrors();
    }

    public function test_user_without_manage_permission_cannot_trigger_mutations(): void
    {
        $user = $this->user([AccessPermissions::WAHA_SESSION_VIEW]);
        $this->actingAs($user);

        Livewire::test(WahaConnectionCenter::class)
            ->call('startSession', 'default')
            ->assertForbidden();
    }

    private function createSchema(): void
    {
        Schema::create('MSesiWhatsapp', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeSesi');
            $table->string('NamaSesi')->nullable();
            $table->string('BaseUrlWaha')->nullable();
            $table->string('NomorTerhubung')->nullable();
            $table->string('StatusSesi')->default('Aktif');
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('MPengguna', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaPengguna');
            $table->boolean('NonAktif')->default(false);
        });

        Schema::create('TLogAktivitas', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdPengguna')->nullable();
            $table->string('Modul');
            $table->string('Aksi');
            $table->text('Keterangan')->nullable();
            $table->string('IpAddress')->nullable();
            $table->text('UserAgent')->nullable();
            $table->text('DataSebelumJson')->nullable();
            $table->text('DataSesudahJson')->nullable();
            $table->dateTime('TglAktivitas');
            $table->dateTime('TglBuat');
            $table->string('DibuatOleh')->nullable();
            $table->dateTime('TglEdit')->nullable();
        });
    }

    private function seedFixtures(): void
    {
        DB::table('MSesiWhatsapp')->insert([
            'Id' => 'session-1',
            'KodeSesi' => 'default',
            'NamaSesi' => 'Default Session',
            'BaseUrlWaha' => config('services.waha.base_url', 'http://127.0.0.1:3000'),
            'StatusSesi' => 'Aktif',
            'NonAktif' => false,
        ]);
    }

    private function user(array $permissions = []): Pengguna
    {
        $user = new class extends Pengguna {
            /** @var array<int, string> */
            public array $testPermissions = [];

            public function hasPermissionCode(string $permission): bool
            {
                return in_array($permission, $this->testPermissions, true);
            }
        };

        $user->testPermissions = $permissions;

        return $user->forceFill([
            'Id' => 'user-test-1',
            'NamaPengguna' => 'Test Operator',
            'NonAktif' => false,
        ]);
    }
}
