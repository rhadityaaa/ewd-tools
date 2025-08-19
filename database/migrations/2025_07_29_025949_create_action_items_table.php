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
        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_note_id')->constrained('monitoring_notes')->cascadeOnDelete();
            $table->text('action_description');
            $table->enum('item_type', ['previous_period', 'current_progress', 'next_period']); 
            /**
             * previous_period: Aksi yang dilakukan pada periode sebelumnya
             * current_progress: Aksi yang sedang dilakukan pada periode ini
             * next_period: Aksi yang akan dilakukan pada periode depan
             */
            $table->text('progress_notes')->nullable(); // Progress/catatan untuk 'current_progress'
            $table->string('people_in_charge')->nullable();
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');

            // Referensi ke action item periode sebelumnya (untuk tracking kontinuitas)
            $table->foreignId('previous_action_item_id')->nullable()->constrained('action_items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_items');
    }
};
