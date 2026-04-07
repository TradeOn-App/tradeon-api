<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('reference_month')->nullable()->after('initial_debit');
            $table->unsignedSmallInteger('reference_year')->nullable()->after('reference_month');

            $table->index(['client_id', 'type', 'reference_month', 'reference_year'], 'ct_ref_period_idx');
        });
    }

    public function down(): void
    {
        Schema::table('client_transactions', function (Blueprint $table) {
            $table->dropIndex('ct_ref_period_idx');
            $table->dropColumn(['reference_month', 'reference_year']);
        });
    }
};
