<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('client_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_whatsapp_number')->nullable()->after('guest_email');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_email', 'guest_whatsapp_number']);
            $table->foreignId('client_id')->nullable(false)->change();
        });
    }
};
