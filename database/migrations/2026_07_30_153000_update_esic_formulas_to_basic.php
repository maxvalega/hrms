<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ESIC Employee/Employer contributions are calculated on Basic,
     * not Gross. Eligibility (GROSS <= 252000) remains unchanged.
     */
    public function up(): void
    {
        DB::table('salary_components')
            ->where('name', 'ESIC Employee')
            ->where('formula', 'GROSS * 0.0075')
            ->update([
                'formula' => 'BASIC * 0.0075',
                'updated_at' => now(),
            ]);

        DB::table('salary_components')
            ->where('name', 'ESIC Employer')
            ->where('formula', 'GROSS * 0.0325')
            ->update([
                'formula' => 'BASIC * 0.0325',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('salary_components')
            ->where('name', 'ESIC Employee')
            ->where('formula', 'BASIC * 0.0075')
            ->update([
                'formula' => 'GROSS * 0.0075',
                'updated_at' => now(),
            ]);

        DB::table('salary_components')
            ->where('name', 'ESIC Employer')
            ->where('formula', 'BASIC * 0.0325')
            ->update([
                'formula' => 'GROSS * 0.0325',
                'updated_at' => now(),
            ]);
    }
};
