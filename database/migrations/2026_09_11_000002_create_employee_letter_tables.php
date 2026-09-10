<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('letter_formats')) {
            Schema::create('letter_formats', function (Blueprint $table) {
                $table->id();
                $table->string('type', 40);
                $table->string('name', 120);
                $table->longText('content')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_name', 190)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->default(0);
                $table->timestamps();
                $table->unique(['type', 'created_by']);
            });
        }

        if (!Schema::hasTable('employee_letters')) {
            Schema::create('employee_letters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_format_id')->nullable();
                $table->string('type', 40);
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('recipient_name', 190);
                $table->string('recipient_email', 190)->nullable();
                $table->string('subject', 255);
                $table->longText('body_html')->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->json('extra_fields')->nullable();
                $table->string('status', 30)->default('issued');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('emailed_at')->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->unsignedBigInteger('created_by')->default(0);
                $table->timestamps();

                $table->index(['created_by', 'type']);
                $table->index('employee_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_letters');
        Schema::dropIfExists('letter_formats');
    }
};
