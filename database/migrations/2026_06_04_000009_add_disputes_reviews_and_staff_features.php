<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('accepted_proposal_id')
                ->nullable()
                ->after('user_id')
                ->constrained('proposals')
                ->nullOnDelete();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('open');
            $table->text('resolution_notes')->nullable();
            $table->string('resolution_outcome')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mission_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['mission_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_reviews');
        Schema::dropIfExists('disputes');

        Schema::table('missions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_proposal_id');
        });
    }
};
