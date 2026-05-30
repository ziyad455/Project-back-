<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateTranslations extends Command
{
    protected $signature = 'translations:generate';
    protected $description = 'Generate missing Arabic translations for all models with HasTranslations trait';

    public function handle(): int
    {
        $this->info('Generating missing Arabic translations...');

        $models = [
            ServiceCategory::class,
            User::class,
            ServiceRequest::class,
            Review::class,
        ];

        foreach ($models as $modelClass) {
            $this->line("Processing: {$modelClass}");

            $items = $modelClass::all();

            foreach ($items as $item) {
                $hasTranslations = method_exists($item, 'getTranslatableFields');
                if (!$hasTranslations) {
                    continue;
                }

                $translatable = $item->getTranslatableFields();
                $missing = false;

                foreach ($translatable as $field) {
                    $exists = \App\Models\Translation::where('translatable_type', $modelClass)
                        ->where('translatable_id', $item->id)
                        ->where('locale', 'ar')
                        ->where('field', $field)
                        ->exists();

                    if (!$exists && !empty($item->{$field})) {
                        $missing = true;
                        break;
                    }
                }

                if ($missing) {
                    $this->line("  Generating for {$modelClass} #{$item->id}");
                    try {
                        $item->generateTranslations();
                    } catch (\Exception $e) {
                        $this->error("  Error for {$modelClass} #{$item->id}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info('Done! All missing translations generated.');
        return Command::SUCCESS;
    }
}
