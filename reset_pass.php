<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'salma.test.final@ajikhdam.com')->first();
if ($user) {
    $user->password = Hash::make('password123');
    $user->save();
    echo "Password updated for salma.test.final@ajikhdam.com\n";
} else {
    echo "User not found\n";
}
