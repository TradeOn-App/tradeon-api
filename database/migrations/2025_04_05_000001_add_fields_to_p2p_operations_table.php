<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->string('wallet_from', 255)->nullable()->after('reference');
            $table->string('wallet_to', 255)->nullable()->after('wallet_from');
            $table->decimal('dollar_quotation', 20, 8)->nullable()->after('wallet_to');
        });
    }

    public function down(): void
    {
        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->dropColumn(['wallet_from', 'wallet_to', 'dollar_quotation']);
        });
    }
};
