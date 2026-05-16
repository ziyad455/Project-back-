<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_offers', function (Blueprint $table) {
            $table->decimal('offered_price', 10, 2)->nullable()->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE request_offers MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'refused') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_offers', function (Blueprint $table) {
            $table->decimal('offered_price', 10, 2)->nullable(false)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE request_offers MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending'");
        }
    }
};
