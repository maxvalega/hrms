<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInAppNotificationsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('in_app_notifications')) {
            return;
        }

        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('module', 64);
            $table->string('action', 64);
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['created_by']);
            $table->index(['module', 'action']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('in_app_notifications');
    }
}
