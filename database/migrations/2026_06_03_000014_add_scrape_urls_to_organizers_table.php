<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            // Newline-separated list of listing/feed URLs to scrape WITHOUT AI
            // (JSON-LD + iCal extractors). Curated per organizer in the admin.
            $table->text('scrape_urls')->nullable()->after('events_url');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn('scrape_urls');
        });
    }
};
