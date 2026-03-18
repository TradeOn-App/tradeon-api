<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flow_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entry', 'exit']);
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('network_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 20, 8);
            $table->decimal('amount_usdt_equivalent', 20, 8)->nullable();
            $table->decimal('quotation_at_transaction', 20, 8)->nullable(); // cotação USDT no momento da trava
            $table->string('wallet_origin', 255)->nullable();
            $table->string('wallet_destination', 255)->nullable();
            $table->string('tx_hash', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flow_transactions');
    }
};
