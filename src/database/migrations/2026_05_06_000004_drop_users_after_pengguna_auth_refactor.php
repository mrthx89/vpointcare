<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('Dropping the legacy users table requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
SET XACT_ABORT ON;

BEGIN TRANSACTION;

IF OBJECT_ID(N'sessions', 'U') IS NOT NULL
BEGIN
    DELETE FROM sessions;

    DECLARE @sql nvarchar(max);

    SELECT @sql = STRING_AGG(N'DROP INDEX ' + QUOTENAME(i.name) + N' ON sessions', N'; ')
    FROM sys.indexes i
    WHERE i.object_id = OBJECT_ID(N'sessions')
        AND i.is_primary_key = 0
        AND EXISTS (
            SELECT 1
            FROM sys.index_columns ic
            INNER JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
            WHERE ic.object_id = i.object_id
                AND ic.index_id = i.index_id
                AND c.name = N'user_id'
        );

    IF @sql IS NOT NULL
        EXEC sp_executesql @sql;

    IF COL_LENGTH('sessions', 'user_id') IS NOT NULL
        ALTER TABLE sessions ALTER COLUMN user_id nvarchar(100) NULL;

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'sessions_user_id_index' AND object_id = OBJECT_ID('sessions'))
        CREATE INDEX sessions_user_id_index ON sessions (user_id);
END;

IF OBJECT_ID(N'FK_MPengguna_users', 'F') IS NOT NULL
    ALTER TABLE MPengguna DROP CONSTRAINT FK_MPengguna_users;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MPengguna_UserId' AND object_id = OBJECT_ID('MPengguna'))
    DROP INDEX UX_MPengguna_UserId ON MPengguna;

IF COL_LENGTH('MPengguna', 'UserId') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN UserId;

IF OBJECT_ID(N'users', 'U') IS NOT NULL
    DROP TABLE users;

COMMIT TRANSACTION;
SQL);
    }

    public function down(): void
    {
        // Restore from database backup if the legacy users table is needed again.
    }
};
