<?php

use TextLanguageDetect\TextLanguageDetect;
use TextLanguageDetect\LanguageDetect\TextLanguageDetectException;

# TODO: this requires evaluation
trait LanguageDetectorTrait 
{
    private static $languageDetector;

    # TODO: put into util class
    public function detectLanguage($text, $languages) 
    {
        if (!count($languages)) return;
        if (count($languages) > 10) return; # too many languages, probably no good result?

        if (!static::$languageDetector) {
            static::$languageDetector = new TextLanguageDetect();
            static::$languageDetector->setNameMode(2);
        }

        try {
            $guess = static::$languageDetector->detect($text, count($languages)+2);
            $guess = array_intersect_key($guess, array_flip($languages));
            
            error_log(print_r($guess,1));
            arsort($guess);
            return key($guess);
            
        } catch(TextLanguageDetectException $e) {
        }
    }
}

?>
