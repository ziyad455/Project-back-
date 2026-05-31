<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'provider_selected', 'in_progress', 'completed', 'cancelled', 'open') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'provider_selected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
