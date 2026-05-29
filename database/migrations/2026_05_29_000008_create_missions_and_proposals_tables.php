<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->decimal('hourly_cap', 10, 2)->nullable();
            $table->string('currency', 3)->default('XAF');
            $table->string('status')->default('open');
            $table->timestamp('start_expected_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->boolean('is_moderated')->default(false);
            $table->string('moderation_status')->default('approved');
            $table->foreignId('moderated_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('cover_letter');
            $table->string('pricing_type');
            $table->decimal('amount_fixed', 12, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->unsignedInteger('delivery_days');
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['mission_id', 'user_id']);
            $table->index(['mission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('missions');
    }
};
