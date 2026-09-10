<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('users:transfer-login', [
            'from' => 'sainisoniya813@gmail.com',
            'to' => 'sapna@jemini.co.in',
        ]);
    }

    public function down(): void
    {
        // Irreversible login transfer.
    }
};
