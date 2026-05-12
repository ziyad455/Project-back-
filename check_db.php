<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$affected = User::where('email', 'like', '%naciri%')->update(['role' => 'provider']);
echo "Updated $affected users to provider role.\n";
