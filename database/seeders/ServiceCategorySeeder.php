<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    private const TRANSLATIONS = [
        'Développement Web' => [
            'name' => 'تطوير الويب',
            'description' => 'مواقع إلكترونية، تطبيقات ويب، إلخ.',
        ],
        'Design Graphique' => [
            'name' => 'التصميم الجرافيكي',
            'description' => 'شعارات، نماذج أولية، وسائل تواصل.',
        ],
        'Marketing Digital' => [
            'name' => 'التسويق الرقمي',
            'description' => 'تحسين محركات البحث، إعلانات، تواصل اجتماعي.',
        ],
        'Rédaction & Traduction' => [
            'name' => 'التحرير والترجمة',
            'description' => 'مقالات، تصحيح، ترجمة.',
        ],
        'Assistance Administrative' => [
            'name' => 'المساعدة الإدارية',
            'description' => 'إدخال بيانات، إدارة البريد الإلكتروني.',
        ],
        'Cours de Soutien' => [
            'name' => 'دروس الدعم',
            'description' => 'رياضيات، لغات، برمجة.',
        ],
        'Réparation d\'ordinateurs' => [
            'name' => 'إصلاح الحواسيب',
            'description' => 'صيانة وإصلاح معدات الحاسوب.',
        ],
    ];

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
            ['name' => 'Réparation d\'ordinateurs', 'description' => 'Maintenance et réparation de matériel informatique.'],
        ];

        foreach ($categories as $category) {
            $cat = ServiceCategory::updateOrCreate(['name' => $category['name']], $category);

            $ar = self::TRANSLATIONS[$category['name']] ?? null;
            if ($ar) {
                foreach (['name', 'description'] as $field) {
                    Translation::updateOrCreate(
                        [
                            'translatable_type' => ServiceCategory::class,
                            'translatable_id' => $cat->id,
                            'locale' => 'ar',
                            'field' => $field,
                        ],
                        ['content' => $ar[$field]]
                    );
                }
            }
        }
    }
}
