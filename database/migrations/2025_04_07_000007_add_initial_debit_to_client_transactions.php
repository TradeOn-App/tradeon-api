<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_transactions', function (Blueprint $table) {
            $table->decimal('initial_debit', 20, 8)->default(0)->after('amount');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->decimal('initial_debit', 20, 8)->default(0)->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('client_transactions', function (Blueprint $table) {
            $table->dropColumn('initial_debit');
        });

        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn('initial_debit');
        });
    }
};
