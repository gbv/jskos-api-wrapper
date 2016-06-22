<?php

class BARTOCCrawler
{
    /**
     * Get HTML title of a web page
     */
    public static function getTitle($url) {
        $html = file_get_contents($url);
        if (!$html or !preg_match("/<title>(.*)<\/title>/siU", $html, $match)) {
            return; 
        }
        $title = preg_replace('/\s+/', ' ', $match[1]);
        $title = preg_replace('/\s*\|\s*BARTOC.org.*/', ' ', $title);
        $title = trim($title);
        return $title;
    }

}

$url = 'http://bartoc.org/en/taxonomy_term/13692';
$url = preg_replace('/taxonomy_term/','taxonomy/term', $url);
echo $url;
echo BARTOCCrawler::getTitle($url);
