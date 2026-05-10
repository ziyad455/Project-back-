<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('service_requests', 'deadline')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->date('deadline')->nullable()->after('city');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('service_requests', 'deadline')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropColumn('deadline');
            });
        }
    }
};
