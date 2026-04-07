<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes em foreign keys e campos de filtro frequentes
        Schema::table('commission_transactions', function (Blueprint $table) {
            $table->index('collaborator_id');
            $table->index('commission_rule_id');
            $table->index('currency_id');
            $table->index('cash_flow_transaction_id');
        });

        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->index('currency_id');
            $table->index('cash_flow_transaction_id');
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->index('currency_id');
            $table->index('network_id');
            $table->index('created_by');
        });

        Schema::table('cash_flow_transactions', function (Blueprint $table) {
            $table->index('currency_id');
            $table->index('network_id');
            $table->index('created_by');
        });

        Schema::table('client_transactions', function (Blueprint $table) {
            $table->index('created_at');
        });

        // unique constraint já existe nesta tabela, não duplicar
    }

    public function down(): void
    {
        Schema::table('commission_transactions', function (Blueprint $table) {
            $table->dropIndex(['collaborator_id']);
            $table->dropIndex(['commission_rule_id']);
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['cash_flow_transaction_id']);
        });

        Schema::table('p2p_operations', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['cash_flow_transaction_id']);
        });

        Schema::table('internal_transactions', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['network_id']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('cash_flow_transactions', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
            $table->dropIndex(['network_id']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('client_transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        // nada a reverter em internal_reports
    }
};
