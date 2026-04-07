<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE internal_transactions DROP CONSTRAINT IF EXISTS internal_transactions_type_check");
        DB::statement("ALTER TABLE internal_transactions ADD CONSTRAINT internal_transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'commission_withdrawal', 'initial_value', 'updated_value'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE internal_transactions DROP CONSTRAINT IF EXISTS internal_transactions_type_check");
        DB::statement("ALTER TABLE internal_transactions ADD CONSTRAINT internal_transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'commission_withdrawal'))");
    }
};
