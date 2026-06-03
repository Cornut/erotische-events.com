<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            // Category taken from the source Google Sheet tab (== sheet name):
            // tantra, bdsm, conscious-relating, festival, mens-work, womens-work.
            $table->string('category')->nullable()->after('verification_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
