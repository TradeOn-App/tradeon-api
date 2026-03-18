<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula transações da banca ao cliente (aportes/saques do Gustavo, etc.)
     */
    public function up(): void
    {
        Schema::create('client_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_flow_transaction_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'allocation']);
            $table->decimal('amount', 20, 8);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'cash_flow_transaction_id']);
            $table->index(['client_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_transactions');
    }
};
