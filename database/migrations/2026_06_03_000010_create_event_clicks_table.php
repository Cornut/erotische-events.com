<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->timestamp('clicked_at');
            $table->char('country', 2)->nullable();
            $table->string('device_type')->default('other');
            $table->string('referrer')->nullable();
            // No IP address is ever stored (GDPR).
            $table->index(['event_id', 'organizer_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_clicks');
    }
};
