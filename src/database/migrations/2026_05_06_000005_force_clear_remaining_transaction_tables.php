<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('Clearing remaining VPoint Care transaction tables requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
SET XACT_ABORT ON;

BEGIN TRANSACTION;

IF COL_LENGTH('TChatD', 'IdAiRespon') IS NOT NULL UPDATE TChatD SET IdAiRespon = NULL;
IF COL_LENGTH('TTicket', 'DibuatDariPesanId') IS NOT NULL UPDATE TTicket SET DibuatDariPesanId = NULL;

IF OBJECT_ID(N'TTicketDLampiran', 'U') IS NOT NULL DELETE FROM TTicketDLampiran;
IF OBJECT_ID(N'TTicketDPenugasan', 'U') IS NOT NULL DELETE FROM TTicketDPenugasan;
IF OBJECT_ID(N'TTicketD', 'U') IS NOT NULL DELETE FROM TTicketD;
IF OBJECT_ID(N'TChatDCatatanInternal', 'U') IS NOT NULL DELETE FROM TChatDCatatanInternal;
IF OBJECT_ID(N'TChatDPenugasan', 'U') IS NOT NULL DELETE FROM TChatDPenugasan;
IF OBJECT_ID(N'TChatD', 'U') IS NOT NULL DELETE FROM TChatD;
IF OBJECT_ID(N'TAiRespon', 'U') IS NOT NULL DELETE FROM TAiRespon;
IF OBJECT_ID(N'TAiPermintaan', 'U') IS NOT NULL DELETE FROM TAiPermintaan;
IF OBJECT_ID(N'TTicket', 'U') IS NOT NULL DELETE FROM TTicket;
IF OBJECT_ID(N'TChat', 'U') IS NOT NULL DELETE FROM TChat;
IF OBJECT_ID(N'TLogWebhookWaha', 'U') IS NOT NULL DELETE FROM TLogWebhookWaha;
IF OBJECT_ID(N'TLogIntegrasi', 'U') IS NOT NULL DELETE FROM TLogIntegrasi;
IF OBJECT_ID(N'TLogError', 'U') IS NOT NULL DELETE FROM TLogError;
IF OBJECT_ID(N'TLogAktivitas', 'U') IS NOT NULL DELETE FROM TLogAktivitas;

COMMIT TRANSACTION;
SQL);
    }

    public function down(): void
    {
        // Transaction data deletion is intentionally not reversible.
    }
};
