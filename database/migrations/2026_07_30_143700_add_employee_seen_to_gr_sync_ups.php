<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeSeenToGrSyncUps extends Migration
{
    public function up()
    {
        Schema::table('gr_sync_ups', function (Blueprint $table) {
            if (!Schema::hasColumn('gr_sync_ups', 'employee_seen')) {
                $table->boolean('employee_seen')->default(false)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('gr_sync_ups', function (Blueprint $table) {
            if (Schema::hasColumn('gr_sync_ups', 'employee_seen')) {
                $table->dropColumn('employee_seen');
            }
        });
    }
}
