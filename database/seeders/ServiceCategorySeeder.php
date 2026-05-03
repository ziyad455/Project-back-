<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Développement Web', 'description' => 'Sites web, applications web, etc.'],
            ['name' => 'Design Graphique', 'description' => 'Logos, maquettes, supports de communication.'],
            ['name' => 'Marketing Digital', 'description' => 'SEO, Publicité, Réseaux sociaux.'],
            ['name' => 'Rédaction & Traduction', 'description' => 'Articles, correction, traduction.'],
            ['name' => 'Assistance Administrative', 'description' => 'Saisie de données, gestion d\'emails.'],
            ['name' => 'Cours de Soutien', 'description' => 'Maths, Langues, Programmation.'],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
