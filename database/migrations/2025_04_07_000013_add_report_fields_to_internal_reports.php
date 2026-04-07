<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_reports', function (Blueprint $table) {
            $table->decimal('initial_value', 20, 8)->default(0)->after('balance');
            $table->decimal('updated_value', 20, 8)->default(0)->after('initial_value');
            $table->decimal('profit', 20, 8)->default(0)->after('updated_value');
            $table->decimal('profit_percentage', 10, 4)->default(0)->after('profit');
            $table->decimal('commission_rate', 8, 4)->default(0)->after('profit_percentage');
            $table->decimal('commission_value', 20, 8)->default(0)->after('commission_rate');
            $table->decimal('next_month_initial', 20, 8)->default(0)->after('commission_value');
        });
    }

    public function down(): void
    {
        Schema::table('internal_reports', function (Blueprint $table) {
            $table->dropColumn(['initial_value', 'updated_value', 'profit', 'profit_percentage', 'commission_rate', 'commission_value', 'next_month_initial']);
        });
    }
};
