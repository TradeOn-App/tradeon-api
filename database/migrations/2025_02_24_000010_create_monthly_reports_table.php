<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relatórios mensais enviados/visualizados pelo cliente (Gustavo)
     */
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('total_deposits', 20, 8)->default(0);
            $table->decimal('total_withdrawals', 20, 8)->default(0);
            $table->decimal('profitability_percent', 10, 4)->nullable();
            $table->json('summary')->nullable(); // dados extras (por moeda, etc.)
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'month', 'year']);
            $table->index(['client_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
