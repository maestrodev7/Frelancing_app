<?php

use Database\Seeders\CountrySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new CountrySeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reference data: keep countries on rollback to avoid breaking existing users.
    }
};
