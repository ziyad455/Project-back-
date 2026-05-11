<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslationService
{
    protected $locales = ['ar', 'fr', 'en'];

    public function __construct()
    {
    }

    /**
     * Translate a string into target locales.
     * If $target is provided, returns just that translation string.
     * If $target is null, returns array ['en' => '...', 'ar' => '...', 'fr' => '...']
     */
    public function translateString(string $text, string $source = 'fr', ?string $target = null)
    {
        if (empty($text)) {
            return $target ? "" : array_fill_keys($this->locales, "");
        }

        if ($target) {
            if ($source === $target) return $text;
            try {
                $tr = new GoogleTranslate();
                $tr->setSource($source);
                $tr->setTarget($target);
                $res = $tr->translate($text);
                return $res;
            } catch (\Exception $e) {
                Log::error("Google Translation (single) Exception: " . $e->getMessage());
                return $text;
            }
        }

        $translations = [];
        foreach ($this->locales as $locale) {
            if ($locale === $source) {
                $translations[$locale] = $text;
                continue;
            }
            try {
                $tr = new GoogleTranslate();
                $tr->setSource($source);
                $tr->setTarget($locale);
                
                $translations[$locale] = $tr->translate($text);
                
                usleep(50000); // 50ms delay to avoid rate limit
            } catch (\Exception $e) {
                Log::error("Google Translation Exception for locale $locale: " . $e->getMessage());
                $translations[$locale] = $text;
            }
        }

        return $translations;
    }

    /**
     * Translate an array of strings (handles associative arrays and nested arrays).
     */
    public function translateArray(array $items, string $source = 'fr'): array
    {
        if (empty($items)) return array_fill_keys($this->locales, []);

        $translations = [];
        foreach ($this->locales as $locale) {
            $translations[$locale] = [];
        }

        foreach ($items as $key => $value) {
            // Skip technical keys
            if (in_array($key, ['url', 'path', 'href', 'image', 'icon', 'slug', 'id', 'type', 'section', 'image_path'])) {
                foreach ($this->locales as $locale) {
                    $translations[$locale][$key] = $value;
                }
                continue;
            }

            if (is_array($value)) {
                // Recursive call for nested arrays
                $res = $this->translateArray($value, $source);
                foreach ($this->locales as $locale) {
                    $translations[$locale][$key] = $res[$locale] ?? $value;
                }
            } elseif (is_string($value) && !empty($value) && !is_numeric($value) && strlen($value) > 1) {
                // Translate strings
                $res = $this->translateString($value, $source);
                foreach ($this->locales as $locale) {
                    $translations[$locale][$key] = $res[$locale] ?? $value;
                }
            } else {
                // Keep numbers, nulls, or very short strings as is
                foreach ($this->locales as $locale) {
                    $translations[$locale][$key] = $value;
                }
            }
        }

        return $translations;
    }
}
