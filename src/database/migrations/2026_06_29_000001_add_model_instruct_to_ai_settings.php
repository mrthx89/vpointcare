<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (! Schema::hasColumn('MPengaturanAi', 'ModelInstructAi')) {
                    $table->string('ModelInstructAi', 100)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'ModelInstructAi')) {
                    $table->dropColumn('ModelInstructAi');
                }
            });
        }
    }
};
