<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->decimal('initial_value', 20, 8)->default(0)->after('profitability_percent');
            $table->decimal('updated_value', 20, 8)->default(0)->after('initial_value');
            $table->decimal('real_gain', 20, 8)->default(0)->after('updated_value');
            $table->decimal('gain_percentage', 10, 4)->default(0)->after('real_gain');
            $table->decimal('commission_value', 20, 8)->default(0)->after('gain_percentage');
            $table->decimal('profit_value', 20, 8)->default(0)->after('commission_value');
            $table->decimal('next_month_initial', 20, 8)->default(0)->after('profit_value');
            $table->decimal('commission_rate', 8, 4)->default(0)->after('next_month_initial');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_reports', function (Blueprint $table) {
            $table->dropColumn([
                'initial_value', 'updated_value', 'real_gain', 'gain_percentage',
                'commission_value', 'profit_value', 'next_month_initial', 'commission_rate',
            ]);
        });
    }
};
