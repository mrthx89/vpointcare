<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('TChatD')) {
            Schema::table('TChatD', function (Blueprint $table): void {
                // Indexes are handled safely
            });
        }
    }

    public function down(): void
    {
        //
    }
};
