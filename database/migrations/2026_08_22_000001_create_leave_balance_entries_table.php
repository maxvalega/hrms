<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('leave_balance_entries')) {
            return;
        }

        Schema::create('leave_balance_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('leave_type_id')->index();
            $table->string('entry_type', 32); // opening | grant | adjustment
            $table->decimal('days', 8, 2)->default(0);
            $table->string('period_key', 16)->nullable()->index(); // e.g. 2026 or 2026-08
            $table->text('notes')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'leave_type_id', 'entry_type'], 'lbe_emp_type_entry_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_balance_entries');
    }
};
