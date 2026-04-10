<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_reports', function (Blueprint $table) {
            $table->decimal('cumulative_deposits', 20, 8)->default(0)->after('total_deposits');
        });
    }

    public function down(): void
    {
        Schema::table('internal_reports', function (Blueprint $table) {
            $table->dropColumn('cumulative_deposits');
        });
    }
};
