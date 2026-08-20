<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('TAiDraftPengetahuan')) {
            Schema::create('TAiDraftPengetahuan', function (Blueprint $table): void {
                $table->uuid('Id')->primary();
                $table->uuid('IdChat')->nullable()->index();
                $table->uuid('IdPengetahuan')->nullable()->index();
                $table->string('Judul', 200);
                $table->text('Konten');
                $table->string('Ringkasan', 500)->nullable();
                $table->string('Topik', 100)->nullable();
                $table->string('StatusReview', 30)->default('Draft');
                $table->string('HashKonten', 64)->nullable()->index();
                $table->timestamp('TglBuat')->useCurrent();
                $table->uuid('DibuatOleh')->nullable();
                $table->timestamp('TglEdit')->nullable();
                $table->uuid('DieditOleh')->nullable();

                $table->foreign('IdChat')->references('Id')->on('TChat')->nullOnDelete();
                $table->foreign('IdPengetahuan')->references('Id')->on('MPengetahuan')->nullOnDelete();
                $table->index(['StatusReview', 'TglBuat']);
            });
        }

        if (Schema::hasTable('MPengetahuan')) {
            Schema::table('MPengetahuan', function (Blueprint $table): void {
                if (! Schema::hasColumn('MPengetahuan', 'SearchKeywords')) {
                    $table->string('SearchKeywords', 1000)->nullable();
                }
                if (! Schema::hasColumn('MPengetahuan', 'PrioritasAi')) {
                    $table->integer('PrioritasAi')->default(0);
                }
                if (! Schema::hasColumn('MPengetahuan', 'TerakhirDipakaiAi')) {
                    $table->timestamp('TerakhirDipakaiAi')->nullable();
                }
                if (! Schema::hasColumn('MPengetahuan', 'JumlahDipakaiAi')) {
                    $table->integer('JumlahDipakaiAi')->default(0);
                }
            });
        }

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (! Schema::hasColumn('TChat', 'ModeKnowledgeAi')) {
                    $table->string('ModeKnowledgeAi', 30)->default('Auto');
                }
                if (! Schema::hasColumn('TChat', 'BatasKnowledgeAi')) {
                    $table->integer('BatasKnowledgeAi')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('TAiDraftPengetahuan');

        if (Schema::hasTable('TChat')) {
            Schema::table('TChat', function (Blueprint $table): void {
                if (Schema::hasColumn('TChat', 'BatasKnowledgeAi')) {
                    $table->dropColumn('BatasKnowledgeAi');
                }
                if (Schema::hasColumn('TChat', 'ModeKnowledgeAi')) {
                    $table->dropColumn('ModeKnowledgeAi');
                }
            });
        }

        if (Schema::hasTable('MPengetahuan')) {
            Schema::table('MPengetahuan', function (Blueprint $table): void {
                if (Schema::hasColumn('MPengetahuan', 'JumlahDipakaiAi')) {
                    $table->dropColumn('JumlahDipakaiAi');
                }
                if (Schema::hasColumn('MPengetahuan', 'TerakhirDipakaiAi')) {
                    $table->dropColumn('TerakhirDipakaiAi');
                }
                if (Schema::hasColumn('MPengetahuan', 'PrioritasAi')) {
                    $table->dropColumn('PrioritasAi');
                }
                if (Schema::hasColumn('MPengetahuan', 'SearchKeywords')) {
                    $table->dropColumn('SearchKeywords');
                }
            });
        }
    }
};
