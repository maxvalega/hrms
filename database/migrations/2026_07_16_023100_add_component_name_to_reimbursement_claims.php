<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allows free-text reimbursement component names (instead of salary-component dropdown only).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        Schema::table('reimbursement_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('reimbursement_claims', 'component_name')) {
                $table->string('component_name')->nullable()->after('component_id');
            }
        });

        // Allow claims without a salary_components row when using free text
        try {
            DB::statement('ALTER TABLE `reimbursement_claims` MODIFY `component_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or engine differs
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        Schema::table('reimbursement_claims', function (Blueprint $table) {
            if (Schema::hasColumn('reimbursement_claims', 'component_name')) {
                $table->dropColumn('component_name');
            }
        });
    }
};
