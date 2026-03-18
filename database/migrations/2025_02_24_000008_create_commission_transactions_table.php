<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_flow_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commission_rule_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 8);
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_transactions');
    }
};
