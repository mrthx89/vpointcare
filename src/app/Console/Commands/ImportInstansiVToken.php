<?php

namespace App\Console\Commands;

use App\Jobs\ImportVTokenCustomersToInstansi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ImportInstansiVToken extends Command
{
    protected $signature = 'vpoint:import-instansi-vtoken {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Import data customer VToken ke tabel MInstansi berdasarkan kode instansi.';

    public function handle(): int
    {
        $job = new ImportVTokenCustomersToInstansi;

        if ($this->option('sync')) {
            Bus::dispatchSync($job);

            $this->info('Import instansi VToken selesai dijalankan.');

            return self::SUCCESS;
        }

        dispatch($job);

        $this->info('Job import instansi VToken sudah masuk queue.');

        return self::SUCCESS;
    }
}
