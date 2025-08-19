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
        Schema::create('question_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aspect_version_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->text('question_text');
            $table->decimal('weight', 5, 2); // Bobot dalam persen
            $table->decimal('max_score', 5, 2);
            $table->decimal('min_score', 5, 2);
            $table->boolean('is_mandatory')->default(false);
            $table->timestamp('effective_from');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_versions');
    }
};
