<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaborator_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('total_deposits', 20, 8)->default(0);
            $table->decimal('total_withdrawals', 20, 8)->default(0);
            $table->decimal('total_commission_withdrawals', 20, 8)->default(0);
            $table->decimal('balance', 20, 8)->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['collaborator_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_reports');
    }
};
