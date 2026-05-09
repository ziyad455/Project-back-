<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ServiceRequest;

$u = User::find(4);
if ($u) {
    $u->categories()->sync([1, 2]);
    echo "User 4 categories updated.\n";
}

$m = ServiceRequest::create([
    'client_name' => 'John Doe',
    'client_email' => 'john@example.com',
    'client_phone' => '0600000000',
    'title' => 'Besoin d\'un site web e-commerce',
    'category_id' => 1,
    'description' => 'Un site web pour vendre des produits locaux.',
    'budget' => 5000,
    'city' => 'Casablanca',
    'status' => 'open',
    'deadline' => '2026-06-01'
]);
echo "Mission created with ID: " . $m->id . "\n";
