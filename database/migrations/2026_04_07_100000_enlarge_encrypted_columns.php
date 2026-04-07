<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos criptografados precisam de TEXT pois o ciphertext é mais longo que varchar(255)
        Schema::table('cash_flow_transactions', function (Blueprint $table) {
            $table->text('wallet_origin')->nullable()->change();
            $table->text('wallet_destination')->nullable()->change();
            $table->text('tx_hash')->nullable()->change();
        });

        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->text('wallet_from')->nullable()->change();
            $table->text('wallet_to')->nullable()->change();
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->text('wallet_destination')->nullable()->change();
            $table->text('tx_hash')->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->text('document')->change();
            $table->text('phone')->nullable()->change();
        });

        Schema::table('collaborators', function (Blueprint $table) {
            $table->text('cpf')->change();
            $table->text('wallet')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flow_transactions', function (Blueprint $table) {
            $table->string('wallet_origin', 255)->nullable()->change();
            $table->string('wallet_destination', 255)->nullable()->change();
            $table->string('tx_hash', 255)->nullable()->change();
        });

        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->string('wallet_from', 255)->nullable()->change();
            $table->string('wallet_to', 255)->nullable()->change();
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->string('wallet_destination', 255)->nullable()->change();
            $table->string('tx_hash', 255)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('document', 20)->change();
            $table->string('phone', 20)->nullable()->change();
        });

        Schema::table('collaborators', function (Blueprint $table) {
            $table->string('cpf', 14)->change();
            $table->string('wallet', 255)->nullable()->change();
        });
    }
};
