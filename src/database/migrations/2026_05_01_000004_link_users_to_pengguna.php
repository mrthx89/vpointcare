<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy users are no longer linked to MPengguna. Kept as a no-op for migration history.
    }

    public function down(): void
    {
        // No-op.
    }
};
