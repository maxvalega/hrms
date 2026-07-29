<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('grievance_responses')) {
            return;
        }

        Schema::create('grievance_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grievance_id');
            $table->unsignedBigInteger('responder_id');
            $table->text('message');
            $table->enum('response_type', ['hr_response', 'employee_reply', 'system_note'])->default('hr_response');
            $table->boolean('is_internal_note')->default(false)->comment('Visible only to HR staff');
            $table->timestamps();
            
            $table->foreign('grievance_id')->references('id')->on('grievances')->onDelete('cascade');
            $table->foreign('responder_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['grievance_id', 'created_at']);
            $table->index(['responder_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grievance_responses');
    }
};
