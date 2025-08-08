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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('action');
            $table->tinyInteger('approval_step')->nullable();
            $table->tinyInteger('from_step')->nullable();
            $table->tinyInteger('to_step')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('action_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'action_at']);
            $table->index(['user_id', 'action']);
            $table->index(['approval_step', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
