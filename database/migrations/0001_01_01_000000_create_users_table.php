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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('whatsapp_number')->nullable();
            $table->string('city')->nullable();
            $table->enum('role', ['client', 'provider'])->default('client');
            $table->boolean('is_admin')->default(false);
            
            // Talent specific fields
            $table->boolean('is_verified_student')->default(false);
            $table->string('document_id_card')->nullable();
            $table->string('document_student_proof')->nullable();
            $table->string('university')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('title')->nullable(); // e.g., 'Full Stack Developer'
            $table->text('bio')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->json('skills')->nullable(); // Store array of skills
            $table->decimal('hourly_rate', 8, 2)->nullable();
            
            // Stats
            $table->integer('completed_jobs')->default(0);
            $table->decimal('job_success_rate', 5, 2)->nullable(); // e.g., 98.50
            $table->integer('total_votes')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
