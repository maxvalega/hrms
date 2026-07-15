<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = App\Models\Payroll::find(75);
if (!$p) { echo "NOT_FOUND\n"; exit; }
echo 'MONTH=' . $p->month . PHP_EOL;
echo 'GROSS=' . $p->gross_salary . PHP_EOL;
echo 'DEDUCTIONS=' . json_encode($p->deductions_json) . PHP_EOL;
echo 'EARNINGS=' . json_encode($p->earnings_json) . PHP_EOL;
echo 'STAT=' . json_encode($p->statutory_json) . PHP_EOL;
