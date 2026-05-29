<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'provider_selected', 'in_progress', 'completed', 'cancelled', 'open') NOT NULL DEFAULT 'open'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'provider_selected', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
