<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('attendance_regularisations')) {
            return;
        }

        Schema::create('attendance_regularisations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->date('date');
            $table->string('type', 32)->default('on_ground'); // on_ground
            $table->text('reason');
            $table->string('status', 32)->default('Pending'); // Pending | Approved | Reject
            $table->text('manager_comment')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date', 'type'], 'ar_emp_date_type_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_regularisations');
    }
};
