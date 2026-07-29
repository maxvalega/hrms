<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserVisibilityToDucumentUploads extends Migration
{
    public function up()
    {
        Schema::table('ducument_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('ducument_uploads', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('ducument_uploads', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('uploaded_by');
            }
        });
    }

    public function down()
    {
        Schema::table('ducument_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('ducument_uploads', 'assigned_user_id')) {
                $table->dropColumn('assigned_user_id');
            }
            if (Schema::hasColumn('ducument_uploads', 'uploaded_by')) {
                $table->dropColumn('uploaded_by');
            }
        });
    }
}
