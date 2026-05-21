<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country_id')) {
                $table->foreignId('country_id')
                    ->nullable()
                    ->after('phone')
                    ->constrained()
                    ->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('client_profiles', 'country_id')) {
            DB::statement(
                'UPDATE users SET country_id = (
                    SELECT country_id FROM client_profiles WHERE client_profiles.user_id = users.id
                ) WHERE EXISTS (
                    SELECT 1 FROM client_profiles WHERE client_profiles.user_id = users.id
                )'
            );

            Schema::table('client_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('country_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('client_profiles', 'country_id')) {
                $table->foreignId('country_id')
                    ->nullable()
                    ->after('billing_address')
                    ->constrained()
                    ->restrictOnDelete();
            }
        });

        DB::statement(
            'UPDATE client_profiles SET country_id = (
                SELECT country_id FROM users WHERE users.id = client_profiles.user_id
            ) WHERE EXISTS (
                SELECT 1 FROM users WHERE users.id = client_profiles.user_id
            )'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
