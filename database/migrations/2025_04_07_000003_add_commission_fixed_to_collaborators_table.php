<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->decimal('commission', 8, 4)->nullable()->after('wallet');
            $table->decimal('fixed', 20, 8)->nullable()->after('commission');
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropColumn(['commission', 'fixed']);
        });
    }
};
