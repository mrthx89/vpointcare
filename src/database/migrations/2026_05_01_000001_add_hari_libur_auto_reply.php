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
                if (! Schema::hasColumn('MPengaturanAi', 'GunakanAutoReplyHariLibur')) {
                    $table->boolean('GunakanAutoReplyHariLibur')->default(true);
                }
                if (! Schema::hasColumn('MPengaturanAi', 'TemplateHariLibur')) {
                    $table->text('TemplateHariLibur')->nullable();
                }
            });
        }

        if (Schema::hasTable('MHariLibur')) {
            Schema::table('MHariLibur', function (Blueprint $table): void {
                if (! Schema::hasColumn('MHariLibur', 'NamaHariLibur')) {
                    $table->string('NamaHariLibur', 150)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MPengaturanAi')) {
            Schema::table('MPengaturanAi', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengaturanAi', 'GunakanAutoReplyHariLibur')) {
                    $table->dropColumn('GunakanAutoReplyHariLibur');
                }
            });
        }
    }
};
