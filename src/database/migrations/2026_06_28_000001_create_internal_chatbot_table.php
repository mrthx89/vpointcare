<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('TChatbotInternal')) {
            Schema::create('TChatbotInternal', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdPengguna');
                $table->string('Role', 20);
                $table->text('Pesan');
                $table->string('ProviderAi', 50)->nullable();
                $table->string('ModelAi', 100)->nullable();
                $table->text('KnowledgeDipakai')->nullable();
                $table->integer('TokensUsed')->nullable();
                $table->timestamp('TglBuat')->useCurrent();

                $table->foreign('IdPengguna')->references('Id')->on('MPengguna')->cascadeOnDelete();
                $table->index(['IdPengguna', 'TglBuat']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('TChatbotInternal');
    }
};
