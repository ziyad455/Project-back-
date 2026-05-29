<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AutoTranslationService
{
    /**
     * Translate a string from French into Arabic.
     */
    public function translateString(string $text, string $source = 'fr', ?string $target = null)
    {
        if (empty($text)) {
            return $target ? "" : ['ar' => ''];
        }

        if ($target) {
            if ($source === $target) return $text;
            try {
                $tr = new GoogleTranslate();
                $tr->setSource($source);
                $tr->setTarget($target);
                return $tr->translate($text);
            } catch (\Exception $e) {
                Log::error("Google Translation (single) Exception: " . $e->getMessage());
                return $text;
            }
        }

        $targetLocale = 'ar';
        try {
            $tr = new GoogleTranslate();
            $tr->setSource($source);
            $tr->setTarget($targetLocale);
            $translated = $tr->translate($text);
            usleep(50000);
            return ['ar' => $translated];
        } catch (\Exception $e) {
            Log::error("Google Translation Exception for locale $targetLocale: " . $e->getMessage());
            return ['ar' => $text];
        }
    }
}
