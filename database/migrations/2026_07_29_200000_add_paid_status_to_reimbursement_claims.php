<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        // Add "paid" so Lock Month can mark approved claims as paid out.
        DB::statement("ALTER TABLE `reimbursement_claims` MODIFY `status` ENUM('pending', 'approved', 'rejected', 'paid') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('reimbursement_claims')) {
            return;
        }

        // Revert any paid rows before shrinking the enum.
        DB::table('reimbursement_claims')->where('status', 'paid')->update(['status' => 'approved']);
        DB::statement("ALTER TABLE `reimbursement_claims` MODIFY `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
