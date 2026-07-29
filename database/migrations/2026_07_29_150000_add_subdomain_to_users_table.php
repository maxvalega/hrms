<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'subdomain')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('subdomain', 63)->nullable()->unique()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'subdomain')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['subdomain']);
                $table->dropColumn('subdomain');
            });
        }
    }
};
