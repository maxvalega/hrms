<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssignedSeenToDucumentUploads extends Migration
{
    public function up()
    {
        Schema::table('ducument_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('ducument_uploads', 'assigned_seen')) {
                $table->boolean('assigned_seen')->default(false)->after('assigned_user_id');
            }
        });
    }

    public function down()
    {
        Schema::table('ducument_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('ducument_uploads', 'assigned_seen')) {
                $table->dropColumn('assigned_seen');
            }
        });
    }
}
