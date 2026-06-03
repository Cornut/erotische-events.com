<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            // Full postal/billing address (from the Impressum) for later invoicing.
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('street')->nullable()->after('description');
            $table->string('postal_code', 20)->nullable()->after('street');
            $table->string('city')->nullable()->after('postal_code');
            $table->string('country', 2)->nullable()->after('city');
            $table->string('vat_id')->nullable()->after('country');
            $table->string('impressum_url')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'street', 'postal_code', 'city', 'country', 'vat_id', 'impressum_url']);
        });
    }
};
