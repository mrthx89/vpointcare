<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create performance indexes untuk optimize Inbox WhatsApp chat loading.
     * Indexes designed for SQL Server dengan ONLINE option untuk minimize downtime.
     */
    public function up(): void
    {
        // Check if running on SQL Server
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            if (method_exists($this, 'command') && $this->command) {
                $this->command->warn('Skipping: Migration designed for SQL Server only');
            }

            return;
        }

        // Index 1: Main message query - TChatD(IdChat, TglPesan)
        // Composite index untuk optimize query: WHERE IdChat = ? ORDER BY TglPesan
        DB::statement("
            IF NOT EXISTS (
                SELECT 1 FROM sys.indexes 
                WHERE name = 'IX_TChatD_IdChat_TglPesan' 
                AND object_id = OBJECT_ID('TChatD')
            )
            BEGIN
                CREATE NONCLUSTERED INDEX IX_TChatD_IdChat_TglPesan
                ON TChatD (IdChat ASC, TglPesan ASC)
                WITH (ONLINE = ON, SORT_IN_TEMPDB = ON)
            END
        ");

        // Index 2: History sort - TChat(TglChatTerakhir DESC)
        // Optimize query: ORDER BY TglChatTerakhir DESC LIMIT 50
        DB::statement("
            IF NOT EXISTS (
                SELECT 1 FROM sys.indexes 
                WHERE name = 'IX_TChat_TglChatTerakhir' 
                AND object_id = OBJECT_ID('TChat')
            )
            BEGIN
                CREATE NONCLUSTERED INDEX IX_TChat_TglChatTerakhir
                ON TChat (TglChatTerakhir DESC)
                WITH (ONLINE = ON)
            END
        ");

        // Index 3-5: History filters - TChat(IdCustomer, IdInstansi, IdNomorWhatsapp)
        // Optimize loadHistoryChats() WHERE conditions
        $historyIndexes = [
            'IX_TChat_IdCustomer' => 'IdCustomer',
            'IX_TChat_IdInstansi' => 'IdInstansi',
            'IX_TChat_IdNomorWhatsapp' => 'IdNomorWhatsapp',
        ];

        foreach ($historyIndexes as $indexName => $column) {
            DB::statement("
                IF NOT EXISTS (
                    SELECT 1 FROM sys.indexes 
                    WHERE name = '{$indexName}' 
                    AND object_id = OBJECT_ID('TChat')
                )
                BEGIN
                    CREATE NONCLUSTERED INDEX {$indexName}
                    ON TChat ({$column} ASC)
                    WITH (ONLINE = ON)
                END
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drop all performance indexes yang dibuat untuk Inbox WhatsApp.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            return;
        }

        $indexes = [
            'TChatD' => ['IX_TChatD_IdChat_TglPesan'],
            'TChat' => [
                'IX_TChat_TglChatTerakhir',
                'IX_TChat_IdCustomer',
                'IX_TChat_IdInstansi',
                'IX_TChat_IdNomorWhatsapp',
            ],
        ];

        foreach ($indexes as $table => $indexList) {
            foreach ($indexList as $indexName) {
                DB::statement("
                    IF EXISTS (
                        SELECT 1 FROM sys.indexes 
                        WHERE name = '{$indexName}' 
                        AND object_id = OBJECT_ID('{$table}')
                    )
                    BEGIN
                        DROP INDEX {$indexName} ON {$table}
                    END
                ");
            }
        }
    }
};
