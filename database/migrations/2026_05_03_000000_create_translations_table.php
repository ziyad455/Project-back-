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
        Schema::create('translations', function (Blueprint $header) {
            $header->id();
            $header->morphs('translatable');
            $header->string('locale')->index();
            $header->string('field')->index();
            $header->text('content')->nullable();
            $header->timestamps();

            $header->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translatable_field_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
