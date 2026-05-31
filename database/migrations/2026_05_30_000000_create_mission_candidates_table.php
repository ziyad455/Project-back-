<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('talent_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'chosen', 'rejected'])->default('pending');
            $table->unique(['mission_id', 'talent_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_candidates');
    }
};
