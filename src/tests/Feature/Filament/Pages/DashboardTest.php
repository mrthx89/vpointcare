<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\Dashboard;
use App\Models\Master\Pengguna;
use App\Support\AccessPermissions;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->name('dashboard-test.livewire.update');
        });
        app('router')->getRoutes()->refreshNameLookups();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('TTicket');
        Schema::dropIfExists('MStatusTicket');
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('MInstansi');
        Schema::dropIfExists('MStatusChat');
        Schema::dropIfExists('MPengaturanAplikasi');
        Schema::dropIfExists('MPeran');
        Schema::dropIfExists('MPengguna');

        parent::tearDown();
    }

    public function test_dashboard_renders_and_calculates_metrics_successfully(): void
    {
        $agent = $this->agent();

        Livewire::actingAs($agent)
            ->test(Dashboard::class)
            ->assertStatus(200)
            ->assertSet('summary.incoming_messages', 1)
            ->assertSet('summary.incoming_chats', 1)
            ->assertSet('summary.outgoing_cs', 1)
            ->assertSet('summary.outgoing_ai', 1);
    }

    private function agent(array $permissions = [AccessPermissions::DASHBOARD_VIEW]): Pengguna
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
            'NamaPengguna' => 'Dashboard Agent',
            'Email' => 'agent@test.local',
            'NonAktif' => false,
            'StatusRegistrasi' => 'approved',
            'IdPeran' => 'role-admin',
        ]);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('TChatD');
        Schema::dropIfExists('TChat');
        Schema::dropIfExists('MInstansi');
        Schema::dropIfExists('MStatusChat');
        Schema::dropIfExists('MPengaturanAplikasi');
        Schema::dropIfExists('MPeran');
        Schema::dropIfExists('MPengguna');

        Schema::create('MPengguna', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaPengguna');
            $table->string('Email')->nullable();
            $table->string('IdPeran')->nullable();
            $table->string('StatusRegistrasi')->default('approved');
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        Schema::create('MPeran', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodePeran');
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        Schema::create('MPengaturanAplikasi', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodePengaturan')->default('DEFAULT');
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        Schema::create('MStatusChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeStatusChat');
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        Schema::create('MInstansi', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NamaInstansi');
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        Schema::create('TChat', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdInstansi')->nullable();
            $table->string('IdStatusChat')->nullable();
            $table->timestamp('TglChatTerakhir')->nullable();
            $table->timestamps();
        });

        Schema::create('TChatD', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('IdChat');
            $table->string('ArahPesan');
            $table->string('StatusKirim')->nullable();
            $table->string('DibalasOleh')->nullable();
            $table->boolean('DihasilkanOlehAi')->nullable()->default(false);
            $table->timestamp('TglPesan');
            $table->timestamps();
        });

        Schema::create('TTicket', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('NomorTicket')->nullable();
            $table->string('IdStatusTicket')->nullable();
            $table->timestamp('TglBuat')->nullable();
            $table->timestamps();
        });

        Schema::create('MStatusTicket', function (Blueprint $table): void {
            $table->string('Id')->primary();
            $table->string('KodeStatusTicket');
            $table->boolean('StatusFinal')->default(false);
            $table->boolean('NonAktif')->default(false);
            $table->timestamps();
        });

        DB::table('MPeran')->insert(['Id' => 'role-admin', 'KodePeran' => 'ADMIN', 'NonAktif' => false]);
        DB::table('MStatusChat')->insert(['Id' => 'status-1', 'KodeStatusChat' => 'DITUTUP', 'NonAktif' => false]);
        DB::table('MStatusTicket')->insert(['Id' => 'status-t-1', 'KodeStatusTicket' => 'SELESAI', 'StatusFinal' => true, 'NonAktif' => false]);
        DB::table('MInstansi')->insert(['Id' => 'inst-1', 'NamaInstansi' => 'PT Test Client', 'NonAktif' => false]);
        DB::table('TChat')->insert(['Id' => 'chat-1', 'IdInstansi' => 'inst-1', 'TglChatTerakhir' => now()]);
        DB::table('TTicket')->insert(['Id' => 'ticket-1', 'NomorTicket' => 'TCK-001', 'IdStatusTicket' => 'status-t-1', 'TglBuat' => now()]);

        DB::table('TChatD')->insert([
            ['Id' => 'msg-1', 'IdChat' => 'chat-1', 'ArahPesan' => 'Masuk', 'StatusKirim' => null, 'DibalasOleh' => null, 'DihasilkanOlehAi' => false, 'TglPesan' => now()->subMinutes(10)],
            ['Id' => 'msg-2', 'IdChat' => 'chat-1', 'ArahPesan' => 'Keluar', 'StatusKirim' => 'Terkirim WAHA', 'DibalasOleh' => 'agent-1', 'DihasilkanOlehAi' => false, 'TglPesan' => now()->subMinutes(5)],
            ['Id' => 'msg-3', 'IdChat' => 'chat-1', 'ArahPesan' => 'Keluar', 'StatusKirim' => 'Terkirim WAHA', 'DibalasOleh' => null, 'DihasilkanOlehAi' => true, 'TglPesan' => now()->subMinutes(2)],
        ]);
    }
}
