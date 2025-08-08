<?php

use App\Enum\ApprovalStatus;
use App\Enum\WorkflowStep;
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
        Schema::create('report_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->tinyInteger('approval_step')->default(WorkflowStep::UNIT_BISNIS->value);
            $table->tinyInteger('status')->default(ApprovalStatus::PENDING->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            
            /** Constraint and Index */
            $table->unique(['report_id', 'approval_step']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'approval_step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_approvals');
    }
};
