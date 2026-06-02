<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->string('main_image')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('draft');
            $table->json('audience')->nullable();
            $table->unsignedInteger('min_participants')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->json('languages')->nullable();
            $table->string('accommodation')->default('none');
            $table->string('currency', 3)->default('EUR');
            $table->string('booking_url');
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'start_date', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
