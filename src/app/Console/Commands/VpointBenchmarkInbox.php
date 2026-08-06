<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Option;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('vpoint:benchmark-inbox')]
#[Description('Benchmark Inbox WhatsApp chat loading performance')]
#[Option('messages', description: 'Number of test messages to create', default: 100)]
#[Option('iterations', description: 'Number of benchmark iterations', default: 10)]
class VpointBenchmarkInbox extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $messageCount = (int) $this->option('messages');
        $iterations = (int) $this->option('iterations');

        $this->info("VPoint Inbox WhatsApp Benchmark");
        $this->info("Messages: {$messageCount}, Iterations: {$iterations}");
        $this->newLine();

        // Create test fixture
        $this->info('Creating test fixture...');
        $chatId = $this->createTestFixture($messageCount);

        if (! $chatId) {
            $this->error('Failed to create test fixture');

            return self::FAILURE;
        }

        $this->info("Test chat created: {$chatId}");
        $this->newLine();

        // Run benchmark iterations
        $this->info('Running benchmark iterations...');
        $durations = [];
        $queryCounts = [];

        $progressBar = $this->output->createProgressBar($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            // Clear query log
            DB::flushQueryLog();
            DB::enableQueryLog();

            // Measure selectChat duration
            $start = microtime(true);

            // Simulate Livewire component behavior
            $this->simulateSelectChat($chatId);

            $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

            // Record metrics
            $durations[] = $duration;
            $queryCounts[] = count(DB::getQueryLog());

            DB::disableQueryLog();

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Calculate statistics
        $stats = $this->calculateStatistics($durations);

        // Display results
        $this->displayResults($stats, $queryCounts);

        // Cleanup fixture
        $this->info('Cleaning up test fixture...');
        $this->cleanupFixture($chatId);

        $this->newLine();
        $this->info('✓ Benchmark completed successfully');

        return self::SUCCESS;
    }

    /**
     * Create test chat fixture dengan messages.
     */
    private function createTestFixture(int $messageCount): ?string
    {
        try {
            $chatId = (string) Str::orderedUuid();
            $sessionId = DB::table('MSesiWhatsapp')->where('NonAktif', false)->value('Id');

            if (! $sessionId) {
                $this->error('No active WhatsApp session found');

                return null;
            }

            // Create test chat
            DB::table('TChat')->insert([
                'Id' => $chatId,
                'IdSesiWhatsapp' => $sessionId,
                'JenisChat' => 'Pribadi',
                'NomorWhatsapp' => '6281234567890',
                'NamaKontak' => 'Benchmark Test Contact',
                'JumlahPesanBelumDibaca' => 0,
                'TglChatTerakhir' => now(),
                'TglBuat' => now(),
                'TglEdit' => now(),
            ]);

            // Create test messages
            $messages = [];
            for ($i = 1; $i <= $messageCount; $i++) {
                $messages[] = [
                    'Id' => (string) Str::orderedUuid(),
                    'IdChat' => $chatId,
                    'ArahPesan' => $i % 2 === 0 ? 'Masuk' : 'Keluar',
                    'JenisPesan' => 'text',
                    'IsiPesan' => "Test message {$i} untuk benchmark",
                    'TglPesan' => now()->subMinutes($messageCount - $i),
                    'StatusKirim' => 'Terkirim',
                    'TglBuat' => now(),
                    'TglEdit' => now(),
                ];
            }

            // Insert messages in chunks untuk performa
            foreach (array_chunk($messages, 100) as $chunk) {
                DB::table('TChatD')->insert($chunk);
            }

            return $chatId;
        } catch (\Exception $e) {
            $this->error("Failed to create fixture: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Simulate selectChat() behavior untuk benchmark.
     */
    private function simulateSelectChat(string $chatId): void
    {
        // Query 1: Load chat messages (simulating loadChatMessages)
        DB::table('TChatD as d')
            ->leftJoin('MPengguna as p', 'p.Id', '=', 'd.DibalasOleh')
            ->where('d.IdChat', $chatId)
            ->orderBy('d.TglPesan')
            ->limit(200)
            ->select('d.*', 'p.NamaPengguna')
            ->get();

        // Query 2: Load history chats (simulating loadHistoryChats)
        DB::table('TChat as c')
            ->leftJoin('MStatusChat as s', 's.Id', '=', 'c.IdStatusChat')
            ->where('c.Id', '!=', $chatId)
            ->where('c.NomorWhatsapp', '6281234567890')
            ->orderByDesc('c.TglChatTerakhir')
            ->select('c.Id', 'c.TglChatTerakhir', 's.NamaStatusChat')
            ->limit(20)
            ->get();

        // Query 3: Load internal notes (simulating loadInternalNotes)
        DB::table('TChatDCatatanInternal')
            ->where('IdChat', $chatId)
            ->orderBy('TglBuat', 'asc')
            ->get();
    }

    /**
     * Calculate statistics dari duration array.
     */
    private function calculateStatistics(array $durations): array
    {
        sort($durations);

        return [
            'mean' => array_sum($durations) / count($durations),
            'min' => min($durations),
            'max' => max($durations),
            'p50' => $this->percentile($durations, 50),
            'p95' => $this->percentile($durations, 95),
        ];
    }

    /**
     * Calculate percentile value.
     */
    private function percentile(array $sorted, int $percentile): float
    {
        $index = (count($sorted) - 1) * ($percentile / 100);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $sorted[(int) $index];
        }

        return $sorted[(int) $lower] + ($index - $lower) * ($sorted[(int) $upper] - $sorted[(int) $lower]);
    }

    /**
     * Display benchmark results.
     */
    private function displayResults(array $stats, array $queryCounts): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Duration', round($stats['mean'], 2).' ms'],
                ['Min Duration', round($stats['min'], 2).' ms'],
                ['Max Duration', round($stats['max'], 2).' ms'],
                ['P50 Duration', round($stats['p50'], 2).' ms'],
                ['P95 Duration', round($stats['p95'], 2).' ms'],
                ['Average Queries', round(array_sum($queryCounts) / count($queryCounts), 1)],
                ['Min Queries', min($queryCounts)],
                ['Max Queries', max($queryCounts)],
            ]
        );

        // Performance assessment
        $this->newLine();
        if ($stats['p95'] < 1000) {
            $this->info('✓ Performance: EXCELLENT (P95 < 1s)');
        } elseif ($stats['p95'] < 2000) {
            $this->warn('⚠ Performance: GOOD (P95 < 2s)');
        } else {
            $this->error('✗ Performance: NEEDS IMPROVEMENT (P95 >= 2s)');
        }
    }

    /**
     * Cleanup test fixture.
     */
    private function cleanupFixture(string $chatId): void
    {
        try {
            DB::table('TChatDCatatanInternal')->where('IdChat', $chatId)->delete();
            DB::table('TChatD')->where('IdChat', $chatId)->delete();
            DB::table('TChat')->where('Id', $chatId)->delete();

            $this->info('✓ Fixture cleaned up');
        } catch (\Exception $e) {
            $this->warn("Failed to cleanup fixture: {$e->getMessage()}");
        }
    }
}
