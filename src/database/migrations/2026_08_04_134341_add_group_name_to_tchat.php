<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('TChat', 'GroupName')) {
            return;
        }

        Schema::table('TChat', function ($table) {
            $table->string('GroupName', 200)->nullable()->after('NamaGrupWhatsapp');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('TChat', 'GroupName')) {
            return;
        }

        Schema::table('TChat', function ($table) {
            $table->dropColumn('GroupName');
        });
    }
};
