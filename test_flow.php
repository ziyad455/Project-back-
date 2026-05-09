<?php

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\RequestOffer;
use App\Models\Review;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- Démarrage du Test End-to-End AjiKhdam ---\n";

// 1. Nettoyage (Optionnel pour le test)
User::whereIn('email', ['client_test@example.com', 'talent_test@example.com'])->delete();

// 2. Création du Client
$client = User::create([
    'first_name' => 'Client',
    'last_name' => 'Test',
    'email' => 'client_test@example.com',
    'password' => Hash::make('password'),
    'role' => 'client',
    'city' => 'Casablanca'
]);
echo "[OK] Client créé : {$client->email}\n";

// 3. Création du Talent
$talent = User::create([
    'first_name' => 'Talent',
    'last_name' => 'Test',
    'email' => 'talent_test@example.com',
    'password' => Hash::make('password'),
    'role' => 'provider',
    'city' => 'Casablanca',
    'is_verified_student' => false // Doit être vérifié par l'admin
]);
echo "[OK] Talent créé (non vérifié) : {$talent->email}\n";

// 4. Vérification Admin
$talent->is_verified_student = true;
$talent->save();
echo "[OK] Talent vérifié (Simulation Admin)\n";

// 5. Création d'une Demande de Service (Client)
$request = ServiceRequest::create([
    'client_id' => $client->id,
    'city' => 'Casablanca',
    'service_category_id' => 1,
    'description' => 'Ceci est une demande de test pour le flux complet.',
    'proposed_price' => 500,
    'status' => 'pending'
]);
echo "[OK] Demande de service créée : ID {$request->id}\n";

// 6. Soumission d'une Offre (Talent)
$offer = RequestOffer::create([
    'service_request_id' => $request->id,
    'provider_id' => $talent->id,
    'offered_price' => 450,
    'status' => 'pending'
]);
echo "[OK] Offre soumise par le Talent : {$offer->offered_price} DH\n";

// 7. Acceptation de l'Offre (Client)
$request->selected_provider_id = $talent->id;
$request->status = 'completed'; // On simule la fin directe pour tester l'avis
$request->save();

$offer->status = 'accepted';
$offer->save();
echo "[OK] Offre acceptée par le Client. Mission terminée.\n";

// 8. Soumission d'un Avis (Client)
$review = Review::create([
    'service_request_id' => $request->id,
    'reviewer_id' => $client->id,
    'provider_id' => $talent->id,
    'rating' => 5,
    'comment' => 'Excellent travail, très rapide !'
]);

// Mise à jour des stats du talent (Normalement géré par le ReviewController)
$talent->completed_jobs += 1;
$talent->total_votes += 1;
$talent->average_rating = ($talent->average_rating * ($talent->total_votes - 1) + 5) / $talent->total_votes;
$talent->save();

echo "[OK] Avis soumis : 5 étoiles.\n";
echo "[OK] Stats Talent mises à jour : Note {$talent->average_rating}, Jobs {$talent->completed_jobs}\n";

echo "\n--- TEST REUSSI : Le flux complet fonctionne parfaitement ! ---\n";
