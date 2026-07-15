<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds receipt attachment path for reimbursement claims (JPEG / PDF).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        Schema::table('reimbursement_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_claims', 'attachment')) {
                $table->string('attachment')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        Schema::table('reimbursement_claims', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_claims', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
