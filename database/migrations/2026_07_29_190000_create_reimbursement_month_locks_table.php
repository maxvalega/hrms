<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reimbursement_month_locks')) {
            return;
        }

        Schema::create('reimbursement_month_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lock_month', 7); // Y-m
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['created_by', 'lock_month']);
            $table->index(['created_by', 'lock_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursement_month_locks');
    }
};
