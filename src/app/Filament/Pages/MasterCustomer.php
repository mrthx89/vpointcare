<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MasterCustomer extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Customer';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Master Customer';

    protected string $view = 'filament.pages.master-customer';

    /** @var array<string, int> */
    public array $stats = [];

    /** @var array<int, array<string, mixed>> */
    public array $instansiRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $kontakRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $nomorRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $grupRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $anggotaGrupRows = [];

    public array $formInstansi = [
        'KodeInstansi' => '',
        'NamaInstansi' => '',
        'Kota' => '',
        'Telepon' => '',
    ];

    public array $formKontak = [
        'IdInstansi' => '',
        'KodeCustomer' => '',
        'NamaCustomer' => '',
        'Jabatan' => '',
        'Email' => '',
        'Telepon' => '',
    ];

    public array $formNomor = [
        'IdCustomer' => '',
        'NomorWhatsapp' => '',
        'NamaKontak' => '',
        'JabatanKontak' => '',
    ];

    public array $formGrup = [
        'IdInstansi' => '',
        'KodeGrup' => '',
        'NamaGrup' => '',
        'IdGrupWaha' => '',
        'Deskripsi' => '',
    ];

    public array $formAnggotaGrup = [
        'IdGrupWhatsapp' => '',
        'IdNomorWhatsapp' => '',
        'PeranAnggota' => '',
    ];

    public function mount(): void
    {
        $this->loadMasterData();
    }

    public function simpanInstansi(): void
    {
        $this->validate([
            'formInstansi.KodeInstansi' => ['required', 'string', 'max:50'],
            'formInstansi.NamaInstansi' => ['required', 'string', 'max:200'],
            'formInstansi.Kota' => ['nullable', 'string', 'max:100'],
            'formInstansi.Telepon' => ['nullable', 'string', 'max:50'],
        ]);

        DB::table('MInstansi')->updateOrInsert([
            'KodeInstansi' => $this->formInstansi['KodeInstansi'],
        ], [
            'NamaInstansi' => $this->formInstansi['NamaInstansi'],
            'Kota' => $this->formInstansi['Kota'] ?: null,
            'Telepon' => $this->formInstansi['Telepon'] ?: null,
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $this->formInstansi = ['KodeInstansi' => '', 'NamaInstansi' => '', 'Kota' => '', 'Telepon' => ''];
        $this->loadMasterData();
        $this->success('Klien / instansi tersimpan.');
    }

    public function simpanKontak(): void
    {
        $this->validate([
            'formKontak.IdInstansi' => ['required'],
            'formKontak.KodeCustomer' => ['required', 'string', 'max:50'],
            'formKontak.NamaCustomer' => ['required', 'string', 'max:200'],
            'formKontak.Jabatan' => ['nullable', 'string', 'max:100'],
            'formKontak.Email' => ['nullable', 'email', 'max:150'],
            'formKontak.Telepon' => ['nullable', 'string', 'max:50'],
        ]);

        DB::table('MCustomer')->updateOrInsert([
            'KodeCustomer' => $this->formKontak['KodeCustomer'],
        ], [
            'IdInstansi' => $this->formKontak['IdInstansi'],
            'NamaCustomer' => $this->formKontak['NamaCustomer'],
            'Jabatan' => $this->formKontak['Jabatan'] ?: null,
            'Email' => $this->formKontak['Email'] ?: null,
            'Telepon' => $this->formKontak['Telepon'] ?: null,
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $this->formKontak = ['IdInstansi' => '', 'KodeCustomer' => '', 'NamaCustomer' => '', 'Jabatan' => '', 'Email' => '', 'Telepon' => ''];
        $this->loadMasterData();
        $this->success('Kontak customer tersimpan.');
    }

    public function simpanNomor(): void
    {
        $this->validate([
            'formNomor.IdCustomer' => ['required'],
            'formNomor.NomorWhatsapp' => ['required', 'string', 'max:30'],
            'formNomor.NamaKontak' => ['nullable', 'string', 'max:150'],
            'formNomor.JabatanKontak' => ['nullable', 'string', 'max:100'],
        ]);

        $kontak = DB::table('MCustomer')->where('Id', $this->formNomor['IdCustomer'])->first();

        DB::table('MNomorWhatsapp')->updateOrInsert([
            'NomorWhatsapp' => $this->normalisasiNomorWhatsapp($this->formNomor['NomorWhatsapp']),
        ], [
            'IdCustomer' => $kontak->Id,
            'IdInstansi' => $kontak->IdInstansi,
            'NamaKontak' => $this->formNomor['NamaKontak'] ?: $kontak->NamaCustomer,
            'JabatanKontak' => $this->formNomor['JabatanKontak'] ?: $kontak->Jabatan,
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $this->formNomor = ['IdCustomer' => '', 'NomorWhatsapp' => '', 'NamaKontak' => '', 'JabatanKontak' => ''];
        $this->loadMasterData();
        $this->success('Nomor WhatsApp tersimpan.');
    }

    public function simpanGrup(): void
    {
        $this->validate([
            'formGrup.IdInstansi' => ['required'],
            'formGrup.KodeGrup' => ['required', 'string', 'max:50'],
            'formGrup.NamaGrup' => ['required', 'string', 'max:200'],
            'formGrup.IdGrupWaha' => ['nullable', 'string', 'max:200'],
            'formGrup.Deskripsi' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('MGrupWhatsapp')->updateOrInsert([
            'KodeGrup' => $this->formGrup['KodeGrup'],
        ], [
            'IdInstansi' => $this->formGrup['IdInstansi'],
            'NamaGrup' => $this->formGrup['NamaGrup'],
            'IdGrupWaha' => $this->formGrup['IdGrupWaha'] ?: null,
            'Deskripsi' => $this->formGrup['Deskripsi'] ?: null,
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $this->formGrup = ['IdInstansi' => '', 'KodeGrup' => '', 'NamaGrup' => '', 'IdGrupWaha' => '', 'Deskripsi' => ''];
        $this->loadMasterData();
        $this->success('Grup WhatsApp tersimpan.');
    }

    public function tambahAnggotaGrup(): void
    {
        $this->validate([
            'formAnggotaGrup.IdGrupWhatsapp' => ['required'],
            'formAnggotaGrup.IdNomorWhatsapp' => ['required'],
            'formAnggotaGrup.PeranAnggota' => ['nullable', 'string', 'max:100'],
        ]);

        $nomor = DB::table('MNomorWhatsapp')->where('Id', $this->formAnggotaGrup['IdNomorWhatsapp'])->first();

        DB::table('MAnggotaGrupWhatsapp')->updateOrInsert([
            'IdGrupWhatsapp' => $this->formAnggotaGrup['IdGrupWhatsapp'],
            'IdNomorWhatsapp' => $this->formAnggotaGrup['IdNomorWhatsapp'],
        ], [
            'IdCustomer' => $nomor->IdCustomer,
            'PeranAnggota' => $this->formAnggotaGrup['PeranAnggota'] ?: null,
            'NonAktif' => false,
            'TglEdit' => now(),
        ]);

        $this->formAnggotaGrup = ['IdGrupWhatsapp' => '', 'IdNomorWhatsapp' => '', 'PeranAnggota' => ''];
        $this->loadMasterData();
        $this->success('Anggota grup tersimpan.');
    }

    private function loadMasterData(): void
    {
        $this->stats = [
            'instansi' => (int) DB::table('MInstansi')->where('NonAktif', false)->count(),
            'kontak' => (int) DB::table('MCustomer')->where('NonAktif', false)->count(),
            'nomor' => (int) DB::table('MNomorWhatsapp')->where('NonAktif', false)->count(),
            'grup' => (int) DB::table('MGrupWhatsapp')->where('NonAktif', false)->count(),
        ];

        $this->instansiRows = $this->rows(DB::table('MInstansi')
            ->select('Id', 'KodeInstansi', 'NamaInstansi', 'Kota', 'Telepon', 'TglSinkronTerakhir')
            ->where('NonAktif', false)
            ->orderBy('NamaInstansi')
            ->limit(50)
            ->get());

        $this->kontakRows = $this->rows(DB::table('MCustomer as c')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'c.IdInstansi')
            ->select('c.Id', 'c.KodeCustomer', 'c.NamaCustomer', 'c.Jabatan', 'c.Email', 'c.Telepon', 'i.NamaInstansi')
            ->where('c.NonAktif', false)
            ->orderBy('i.NamaInstansi')
            ->orderBy('c.NamaCustomer')
            ->limit(80)
            ->get());

        $this->nomorRows = $this->rows(DB::table('MNomorWhatsapp as n')
            ->leftJoin('MCustomer as c', 'c.Id', '=', 'n.IdCustomer')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'n.IdInstansi')
            ->select('n.Id', 'n.NomorWhatsapp', 'n.NamaKontak', 'n.JabatanKontak', 'c.NamaCustomer', 'i.NamaInstansi')
            ->where('n.NonAktif', false)
            ->orderBy('i.NamaInstansi')
            ->orderBy('n.NamaKontak')
            ->limit(100)
            ->get());

        $this->grupRows = $this->rows(DB::table('MGrupWhatsapp as g')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'g.IdInstansi')
            ->select('g.Id', 'g.KodeGrup', 'g.NamaGrup', 'g.IdGrupWaha', 'g.Deskripsi', 'i.NamaInstansi')
            ->where('g.NonAktif', false)
            ->orderBy('i.NamaInstansi')
            ->orderBy('g.NamaGrup')
            ->limit(80)
            ->get());

        $this->anggotaGrupRows = $this->rows(DB::table('MAnggotaGrupWhatsapp as a')
            ->join('MGrupWhatsapp as g', 'g.Id', '=', 'a.IdGrupWhatsapp')
            ->join('MNomorWhatsapp as n', 'n.Id', '=', 'a.IdNomorWhatsapp')
            ->leftJoin('MCustomer as c', 'c.Id', '=', 'a.IdCustomer')
            ->leftJoin('MInstansi as i', 'i.Id', '=', 'g.IdInstansi')
            ->select('g.NamaGrup', 'i.NamaInstansi', 'n.NomorWhatsapp', 'n.NamaKontak', 'c.NamaCustomer', 'a.PeranAnggota')
            ->where('a.NonAktif', false)
            ->orderBy('i.NamaInstansi')
            ->orderBy('g.NamaGrup')
            ->limit(100)
            ->get());
    }

    private function normalisasiNomorWhatsapp(string $nomor): string
    {
        return preg_replace('/[^0-9]/', '', $nomor) ?: $nomor;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rows($rows): array
    {
        return $rows->map(fn (object $row): array => (array) $row)->all();
    }

    private function success(string $message): void
    {
        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
