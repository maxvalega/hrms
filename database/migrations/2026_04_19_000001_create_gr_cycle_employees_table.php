<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gr_cycle_employees')) {
            return;
        }

        Schema::create('gr_cycle_employees', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cycle_id');
            $t->unsignedBigInteger('employee_id');
            $t->enum('status', ['assigned', 'goal_pending', 'goal_submitted', 'review_pending', 'completed'])->default('assigned');
            $t->timestamp('notified_at')->nullable();
            $t->date('goal_deadline')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
            $t->unique(['cycle_id', 'employee_id'], 'uq_cycle_emp');
            $t->index('cycle_id');
            $t->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gr_cycle_employees');
    }
};
