<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            throw new RuntimeException('The user-pengguna link migration requires the sqlsrv database connection.');
        }

        DB::unprepared(<<<'SQL'
IF COL_LENGTH('MPengguna', 'UserId') IS NULL
    ALTER TABLE MPengguna ADD UserId bigint NULL;
SQL);

        DB::unprepared(<<<'SQL'
UPDATE p
SET p.UserId = u.id
FROM MPengguna p
INNER JOIN users u ON u.email = p.Email
WHERE p.UserId IS NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MPengguna_UserId' AND object_id = OBJECT_ID('MPengguna'))
    CREATE UNIQUE INDEX UX_MPengguna_UserId ON MPengguna (UserId) WHERE UserId IS NOT NULL;

IF OBJECT_ID(N'FK_MPengguna_users', 'F') IS NULL
BEGIN
    ALTER TABLE MPengguna
    ADD CONSTRAINT FK_MPengguna_users FOREIGN KEY (UserId) REFERENCES users(id);
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::unprepared(<<<'SQL'
IF OBJECT_ID(N'FK_MPengguna_users', 'F') IS NOT NULL
    ALTER TABLE MPengguna DROP CONSTRAINT FK_MPengguna_users;

IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_MPengguna_UserId' AND object_id = OBJECT_ID('MPengguna'))
    DROP INDEX UX_MPengguna_UserId ON MPengguna;

IF COL_LENGTH('MPengguna', 'UserId') IS NOT NULL
    ALTER TABLE MPengguna DROP COLUMN UserId;
SQL);
    }
};
