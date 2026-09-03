<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (!Schema::hasColumn('holidays', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('occasion');
            }
            if (!Schema::hasColumn('holidays', 'day_type')) {
                $table->string('day_type', 20)->default('full_day')->after('is_optional');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'day_type')) {
                $table->dropColumn('day_type');
            }
            if (Schema::hasColumn('holidays', 'is_optional')) {
                $table->dropColumn('is_optional');
            }
        });
    }
};
