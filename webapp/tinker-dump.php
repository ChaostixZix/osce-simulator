<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$run = App\Models\AiAssessmentRun::with('areaResults')->where('osce_session_id', 1)->latest()->first();
if (!$run) { echo "no run\n"; exit; }
foreach ($run->areaResults as $ar) {
    echo $ar->clinical_area . ": status=" . $ar->status . ", attempts=" . ($ar->attempts ?? 0) . ", err=" . ($ar->error_message ?? '') . "\n";
}
