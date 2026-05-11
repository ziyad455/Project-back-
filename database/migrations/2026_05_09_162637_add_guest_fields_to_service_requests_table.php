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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            $table->string('client_name')->nullable()->after('user_id');
            $table->string('client_email')->nullable()->after('client_name');
            $table->string('client_phone')->nullable()->after('client_email');
            $table->string('title')->nullable()->after('client_phone');
            $table->foreignId('category_id')->nullable()->after('title')->constrained('service_categories')->onDelete('set null');
            $table->decimal('budget', 10, 2)->nullable()->after('description');
            
            // Make client_id and service_category_id nullable if they aren't already
            // Note: SQLite doesn't support modifying columns directly without doctrine/dbal.
            // If they are required for existing code, we keep them.
        });

        // Use a raw query to update the default status or just handle it in the model
        // SQLite is limited, so we might just add 'open' to the status or change it.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            //
        });
    }
};
