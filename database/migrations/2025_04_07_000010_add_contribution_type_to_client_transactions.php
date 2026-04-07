<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE client_transactions DROP CONSTRAINT IF EXISTS client_transactions_type_check");
        DB::statement("ALTER TABLE client_transactions ADD CONSTRAINT client_transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'allocation', 'updated_value', 'contribution'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE client_transactions DROP CONSTRAINT IF EXISTS client_transactions_type_check");
        DB::statement("ALTER TABLE client_transactions ADD CONSTRAINT client_transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'allocation', 'updated_value'))");
    }
};
