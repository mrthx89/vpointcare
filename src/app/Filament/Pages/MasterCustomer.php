<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Master\AnggotaGrupWhatsapps\AnggotaGrupWhatsappResource;
use App\Filament\Resources\Master\Customers\CustomerResource;
use App\Filament\Resources\Master\GrupWhatsapps\GrupWhatsappResource;
use App\Filament\Resources\Master\Instansis\InstansiResource;
use App\Filament\Resources\Master\NomorWhatsapps\NomorWhatsappResource;
use App\Support\AccessPermissions;
use App\Support\FilamentAccess;
use App\Support\NavigationHelper;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MasterCustomer extends Page
{
    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return NavigationHelper::iconFor(AccessPermissions::MASTER_CUSTOMER_VIEW, 'heroicon-o-squares-2x2');
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationHelper::groupFor(AccessPermissions::MASTER_CUSTOMER_VIEW, __('ui.navigation.master_data'));
    }

    public static function getNavigationSort(): ?int
    {
        return NavigationHelper::sortFor(AccessPermissions::MASTER_CUSTOMER_VIEW, 10);
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return __('ui.navigation.master_data');
    }

    public static function getNavigationLabel(): string
    {
        return NavigationHelper::labelFor(AccessPermissions::MASTER_CUSTOMER_VIEW, __('ui.pages.master_customer.flow_title'));
    }

    protected string $view = 'filament.pages.master-customer';

    public static function canAccess(): bool
    {
        return FilamentAccess::can(AccessPermissions::MASTER_CUSTOMER_VIEW)
            && NavigationHelper::isActive(AccessPermissions::MASTER_CUSTOMER_VIEW);
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
                'title' => __('ui.models.instansi.label'),
                'description' => __('ui.pages.master_customer.flow_client_body'),
                'url' => InstansiResource::getUrl('index'),
            ],
            [
                'title' => __('ui.models.customer.label'),
                'description' => __('ui.pages.master_customer.flow_contact_body'),
                'url' => CustomerResource::getUrl('index'),
            ],
            [
                'title' => __('ui.models.nomor_whatsapp.label'),
                'description' => __('ui.pages.master_customer.flow_number_body'),
                'url' => NomorWhatsappResource::getUrl('index'),
            ],
            [
                'title' => __('ui.models.grup_whatsapp.label'),
                'description' => __('ui.pages.master_customer.flow_group_body'),
                'url' => GrupWhatsappResource::getUrl('index'),
            ],
            [
                'title' => __('ui.models.anggota_grup.label'),
                'description' => __('ui.pages.master_customer.group_members'),
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
