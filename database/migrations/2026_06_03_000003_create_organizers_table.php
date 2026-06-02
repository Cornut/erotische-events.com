<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('slug')->unique();
            $table->string('verification_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['verification_status', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
