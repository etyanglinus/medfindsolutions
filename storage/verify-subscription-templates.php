<?php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_DRIVER=array');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$views = ['doctor/dashboard', 'doctor/sidebar', 'doctor/profile/edit_profile', 'doctor/_subscription-summary', 'patient/profile/auth/doctor-subscription', 'patient/profile/auth/clinic-subscription', 'patient/profile/auth/partner-subscription', 'patient/profile/auth/partner-renewal', 'patient/profile/auth/_coupon-quote', 'patient/profile/auth/_coupon-script', 'clinic/subscription-status'];
$scripts = [];
foreach ($views as $view) {
    $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$view.'.blade.php')));
    $temporary = storage_path('framework/views/subscription-syntax-check.php');
    file_put_contents($temporary, $compiled);
    $process = new Symfony\Component\Process\Process([PHP_BINARY, '-l', $temporary]);
    $process->run();
    if (! $process->isSuccessful()) { echo $view.': '.$process->getOutput().$process->getErrorOutput(); exit(1); }
    preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $compiled, $matches);
    foreach ($matches[1] as $index => $script) {
        if (trim($script) === '') continue;
        $script = preg_replace('/<\?php\s+echo\b.*?\?>/s', 'null', $script);
        $script = preg_replace('/<\?php.*?\?>/s', '', $script);
        $scripts[$view.':'.$index] = $script;
    }
}
unlink($temporary);
echo json_encode($scripts);
