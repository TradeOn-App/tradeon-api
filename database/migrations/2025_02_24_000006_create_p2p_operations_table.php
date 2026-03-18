<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2p_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_flow_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 8);
            $table->string('to_whom', 255); // para quem saiu
            $table->text('reason'); // por que saiu (motivo)
            $table->date('operation_date');
            $table->string('reference', 100)->nullable(); // ref externa
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('operation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2p_operations');
    }
};
