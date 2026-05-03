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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('city');
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('proposed_price', 10, 2);
            $table->enum('status', ['pending', 'provider_selected', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('selected_provider_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
