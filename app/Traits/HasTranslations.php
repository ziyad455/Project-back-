<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Boot the trait to add saved listener.
     */
    protected static function bootHasTranslations()
    {
        static::saved(function ($model) {
            $translatableFields = $model->getTranslatableFields();
            $changedFields = [];

            foreach ($translatableFields as $field) {
                if ($model->wasRecentlyCreated || $model->wasChanged($field)) {
                    $changedFields[] = $field;
                } elseif (!Translation::where('translatable_type', get_class($model))
                    ->where('translatable_id', $model->id)
                    ->where('locale', 'ar')
                    ->where('field', $field)
                    ->exists()) {
                    $changedFields[] = $field;
                }
            }

            if (!empty($changedFields)) {
                $model->generateTranslations(array_unique($changedFields));
            }
        });
    }

    /**
     * Get all of the model's translations.
     */
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * Define which fields are translatable.
     * Override this in the model's $translatable property.
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    public function generateTranslations(?array $fields = null)
    {
        $fields = $fields ?? $this->getTranslatableFields();
        $translator = app(\App\Services\AutoTranslationService::class);

        foreach ($fields as $field) {
            $sourceData = $this->{$field};

            if (empty($sourceData)) continue;

            if (is_array($sourceData)) {
                $translatedArray = [];
                foreach ($sourceData as $item) {
                    $result = $translator->translateString((string) $item);
                    $translatedArray[] = $result['ar'] ?? $item;
                }
                $translated = ['ar' => json_encode($translatedArray, JSON_UNESCAPED_UNICODE)];
            } else {
                $translated = $translator->translateString($sourceData);
            }

            foreach ($translated as $locale => $text) {
                Translation::updateOrCreate(
                    [
                        'translatable_type' => get_class($this),
                        'translatable_id' => $this->id,
                        'locale' => $locale,
                        'field' => $field,
                    ],
                    ['content' => $text]
                );
            }
        }
    }

    /**
     * Get a translation for a specific field and locale.
     */
    public function translate(string $field, ?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();

        if ($locale === 'fr') {
            return $this->{$field};
        }

        $translation = Translation::where('translatable_type', get_class($this))
            ->where('translatable_id' , $this->id)
            ->where('locale', $locale)
            ->where('field', $field)
            ->first();

        return $translation ? $translation->content : $this->{$field};
    }
}
