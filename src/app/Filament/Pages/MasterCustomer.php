<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Master\AnggotaGrupWhatsapps\AnggotaGrupWhatsappResource;
use App\Filament\Resources\Master\Customers\CustomerResource;
use App\Filament\Resources\Master\GrupWhatsapps\GrupWhatsappResource;
use App\Filament\Resources\Master\Instansis\InstansiResource;
use App\Filament\Resources\Master\NomorWhatsapps\NomorWhatsappResource;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MasterCustomer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Ringkasan Customer';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Master Customer';

    protected string $view = 'filament.pages.master-customer';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_VIEW);
    }

    /** @var array<string, int> */
    public array $stats = [];

    /** @var array<int, array<string, string>> */
    public array $masterLinks = [];

    /** @var array<int, array<string, mixed>> */
    public array $recentMappings = [];

    public function mount(): void
    {
        $this->stats = [
            'instansi' => (int) DB::table('MInstansi')->where('NonAktif', false)->count(),
            'kontak' => (int) DB::table('MCustomer')->where('NonAktif', false)->count(),
            'nomor' => (int) DB::table('MNomorWhatsapp')->where('NonAktif', false)->count(),
            'grup' => (int) DB::table('MGrupWhatsapp')->where('NonAktif', false)->count(),
            'anggota' => (int) DB::table('MAnggotaGrupWhatsapp')->where('NonAktif', false)->count(),
        ];

        $this->masterLinks = [
            [
                'title' => 'Klien / Instansi',
                'description' => 'Data perusahaan, instansi, dan identitas utama customer.',
                'url' => InstansiResource::getUrl('index'),
            ],
            [
                'title' => 'Kontak Customer',
                'description' => 'Daftar orang/PIC yang berada di bawah tiap klien.',
                'url' => CustomerResource::getUrl('index'),
            ],
            [
                'title' => 'Nomor WhatsApp',
                'description' => 'Nomor pribadi customer yang dipakai untuk identifikasi chat.',
                'url' => NomorWhatsappResource::getUrl('index'),
            ],
            [
                'title' => 'Grup WhatsApp',
                'description' => 'Mapping grup WAHA ke klien agar chat grup langsung dikenali.',
                'url' => GrupWhatsappResource::getUrl('index'),
            ],
            [
                'title' => 'Anggota Grup',
                'description' => 'Relasi nomor WhatsApp customer sebagai anggota grup klien.',
                'url' => AnggotaGrupWhatsappResource::getUrl('index'),
            ],
        ];

        $this->recentMappings = DB::table('MNomorWhatsapp as n')
            ->leftJoin('MCustomer as c', 'c.Id', '=', 'n.IdCustomer')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'n.IdInstansi')
            ->select('i.NamaInstansi', 'c.NamaCustomer', 'n.NamaKontak', 'n.NomorWhatsapp', 'n.TglBuat')
            ->where('n.NonAktif', false)
            ->orderByDesc('n.TglBuat')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }
}
