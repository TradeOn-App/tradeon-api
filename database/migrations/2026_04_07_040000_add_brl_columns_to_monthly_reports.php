<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->decimal('initial_value_brl', 20, 8)->default(0)->after('initial_debit');
            $table->decimal('updated_value_brl', 20, 8)->default(0)->after('initial_value_brl');
            $table->decimal('real_gain_brl', 20, 8)->default(0)->after('updated_value_brl');
            $table->decimal('total_deposits_brl', 20, 8)->default(0)->after('real_gain_brl');
            $table->decimal('total_withdrawals_brl', 20, 8)->default(0)->after('total_deposits_brl');
            $table->decimal('commission_value_brl', 20, 8)->default(0)->after('total_withdrawals_brl');
            $table->decimal('profit_value_brl', 20, 8)->default(0)->after('commission_value_brl');
            $table->decimal('next_month_initial_brl', 20, 8)->default(0)->after('profit_value_brl');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn([
                'initial_value_brl', 'updated_value_brl', 'real_gain_brl',
                'total_deposits_brl', 'total_withdrawals_brl',
                'commission_value_brl', 'profit_value_brl', 'next_month_initial_brl',
            ]);
        });
    }
};
