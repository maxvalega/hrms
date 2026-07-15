<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends leave_types for company leave policy matrix (SL/PL/CL/Comp-off/OH/WFH/Bereavement).
 * Existing columns/behaviour are preserved; new fields are additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Per-type carry forward / encashment (used by LeaveTypeController; additive)
            if (!Schema::hasColumn('leave_types', 'is_carry_forward')) {
                $table->boolean('is_carry_forward')->default(0);
            }
            if (!Schema::hasColumn('leave_types', 'max_carry_forward')) {
                $table->decimal('max_carry_forward', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'is_encashable')) {
                $table->boolean('is_encashable')->default(0);
            }
            if (!Schema::hasColumn('leave_types', 'encash_rate_per_day')) {
                $table->decimal('encash_rate_per_day', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'encash_basis')) {
                $table->string('encash_basis', 50)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'policy_code')) {
                $table->string('policy_code', 50)->nullable()->index();
            }
            if (!Schema::hasColumn('leave_types', 'credit_frequency')) {
                // monthly | annual | earned | monthly_cap
                $table->string('credit_frequency', 30)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'is_prorata')) {
                $table->boolean('is_prorata')->default(true);
            }
            if (!Schema::hasColumn('leave_types', 'eligible_employee_types')) {
                // JSON array of employee_types.code values; empty/null = all
                $table->json('eligible_employee_types')->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'min_notice_days')) {
                $table->unsignedInteger('min_notice_days')->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'notice_rules')) {
                // JSON bands for PL-style timelines
                $table->json('notice_rules')->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'max_consecutive_days')) {
                $table->decimal('max_consecutive_days', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'monthly_limit')) {
                $table->decimal('monthly_limit', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'max_encash_on_exit')) {
                $table->decimal('max_encash_on_exit', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('leave_types', 'requires_family_relation')) {
                $table->boolean('requires_family_relation')->default(false);
            }
            if (!Schema::hasColumn('leave_types', 'is_as_earned')) {
                $table->boolean('is_as_earned')->default(false);
            }
            if (!Schema::hasColumn('leave_types', 'policy_notes')) {
                $table->text('policy_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $cols = [
                'is_carry_forward',
                'max_carry_forward',
                'is_encashable',
                'encash_rate_per_day',
                'encash_basis',
                'policy_code',
                'credit_frequency',
                'is_prorata',
                'eligible_employee_types',
                'min_notice_days',
                'notice_rules',
                'max_consecutive_days',
                'monthly_limit',
                'max_encash_on_exit',
                'requires_family_relation',
                'is_as_earned',
                'policy_notes',
            ];
            $existing = [];
            foreach ($cols as $col) {
                if (Schema::hasColumn('leave_types', $col)) {
                    $existing[] = $col;
                }
            }
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
