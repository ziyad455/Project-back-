<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([ServiceCategorySeeder::class]);

        // Demo Client
        User::create([
            'first_name'         => 'Ghita',
            'last_name'          => 'Alami',
            'email'              => 'ghita@example.com',
            'password'           => Hash::make('password'),
            'role'               => 'client',
            'city'               => 'Casablanca',
            'whatsapp_number'    => '0600000001',
        ]);

        // Demo Admin
        User::create([
            'first_name'         => 'Admin',
            'last_name'          => 'AjiKhdam',
            'email'              => 'admin@ajikhdam.com',
            'password'           => Hash::make('password'),
            'role'               => 'client',
            'is_admin'           => true,
            'city'               => 'Casablanca',
        ]);

        // Demo Providers with varying ratings (for tiered distribution testing)
        $providers = [
            [
                'first_name' => 'Youssef', 
                'last_name' => 'Idrissi',  
                'email' => 'provider1@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&h=200&auto=format&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200&h=675&auto=format&fit=crop',
                'average_rating' => 4.9, 
                'total_votes' => 87, 
                'title' => 'Senior Full Stack Developer',
                'university' => 'ENSIAS',
                'field_of_study' => 'Génie Logiciel',
                'bio' => 'With over 8 years of experience, I specialize in building scalable web applications using React and Node.js.',
                'portfolio_url' => 'https://github.com/youssef',
                'skills' => ['Développement Web', 'Laravel', 'React'],
                'hourly_rate' => 85.00,
                'completed_jobs' => 142,
                'job_success_rate' => 98.00,
            ],
            [
                'first_name' => 'Amine',   
                'last_name' => 'Benjelloun',
                'email' => 'provider2@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=200&h=200&auto=format&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1559028012-481c04fa702d?q=80&w=1200&h=675&auto=format&fit=crop',
                'average_rating' => 4.7, 
                'total_votes' => 54, 
                'title' => 'UI/UX Designer',
                'university' => 'EAC',
                'field_of_study' => 'Architecture & Design',
                'bio' => 'I design interfaces that help teams move from early concepts to polished product launches.',
                'portfolio_url' => 'https://dribbble.com/amine',
                'skills' => ['Design Graphique', 'UI/UX', 'Figma'],
                'hourly_rate' => 75.00,
                'completed_jobs' => 96,
                'job_success_rate' => 97.00,
            ],
            [
                'first_name' => 'Zineb',   
                'last_name' => 'El Fassi',  
                'email' => 'provider3@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=200&h=200&auto=format&fit=crop',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=1200&h=675&auto=format&fit=crop',
                'average_rating' => 4.3, 
                'total_votes' => 21, 
                'title' => 'Digital Marketing Specialist',
                'university' => 'ENCG',
                'field_of_study' => 'Marketing',
                'bio' => 'I help brands grow demand through paid acquisition and search strategy.',
                'portfolio_url' => 'https://linkedin.com/in/zineb',
                'skills' => ['Marketing Digital', 'SEO', 'Google Ads'],
                'hourly_rate' => 60.00,
                'completed_jobs' => 61,
                'job_success_rate' => 94.00,
            ],
        ];

        foreach ($providers as $provider) {
            User::create(array_merge($provider, [
                'password'        => Hash::make('password'),
                'role'            => 'provider',
                'city'            => 'Casablanca',
                'whatsapp_number' => '06' . rand(10000000, 99999999),
                'is_verified_student' => true,
            ]));
        }
    }
}
