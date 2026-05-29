<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VerifiedStudentSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'first_name' => 'Sara',
            'last_name' => 'El',
            'email' => 'sara@test.com',
            'password' => Hash::make('password'),
            'role' => 'provider',
            'is_verified_student' => true,
            'city' => 'Casablanca',
            'title' => 'Graphic Designer',
            'bio' => 'Student at ENCG Casablanca, 3 years experience.',
            'skills' => ['Logo Design', 'Photoshop'],
            'average_rating' => 4.8,
            'total_votes' => 12,
        ]);

        User::create([
            'first_name' => 'Mehdi',
            'last_name' => 'Ben',
            'email' => 'mehdi@test.com',
            'password' => Hash::make('password'),
            'role' => 'provider',
            'is_verified_student' => true,
            'city' => 'Rabat',
            'title' => 'Web Developer',
            'bio' => 'Full stack student, React & Laravel.',
            'skills' => ['React', 'Laravel', 'Tailwind'],
            'average_rating' => 5.0,
            'total_votes' => 8,
        ]);
        
        User::create([
            'first_name' => 'Unverified',
            'last_name' => 'Student',
            'email' => 'unverified@test.com',
            'password' => Hash::make('password'),
            'role' => 'provider',
            'is_verified_student' => false,
            'city' => 'Marrakech',
            'title' => 'Beginner',
            'bio' => 'I am not verified yet.',
            'skills' => ['None'],
        ]);
    }
}
