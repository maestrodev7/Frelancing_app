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
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('bio');
            $table->decimal('hourly_rate_default', 10, 2)->nullable();
            $table->string('currency', 3);
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('completed_jobs_count')->default(0);
            $table->string('availability_status')->default('available');
            $table->string('timezone');
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('freelancer_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_profile_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(3);
            $table->timestamps();

            $table->unique(['freelancer_profile_id', 'skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('freelancer_profiles');
    }
};
