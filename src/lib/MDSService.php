<?php

/**
 * Implements a basic JSKOS concepts endpoint for MDS.
 * Based on screen-scraping.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\URISpaceService;

class MDSService extends Service
{
    protected $supportedParameters = ['notation'];

    private $uriSpaceService;

    public function __construct() {
        $this->uriSpaceService = new URISpaceService([
            'Concept' => [
                'uriSpace' => 'http://www.librarything.com/mds/',
                'notationPattern' => '/^[0-9]([0-9]([0-9](\.[0-9]+)?)?)?$/'
            ]
        ]);
        parent::__construct();
    }

    /**
     * Perform entity lookup query.
     */
    public function query($query) {
        $jskos = $this->uriSpaceService->query($query);
        if (!$jskos) return;

        # screen scraping
        $html = file_get_contents($jskos->uri);
        if(empty($html)) return;
        $dom = new DOMDocument();
        libxml_use_internal_errors(TRUE); 
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // XPath function contains(...) is wrong but works in this case
        $levels = $xpath->query('//table[@class="ddc"]//td[contains(@class,"chosen")]');
        $jskos->ancestors = [];

        // get all levels. LibraryThing does not know about table entries
        // so most notations longer than three digits are wrong, but it's
        // a start
        foreach($levels as $td) {
            $concept = static::scrapeCell($td, $xpath);
            $jskos->ancestors[] = $concept; 
        }
        $class = array_pop($jskos->ancestors);
        $jskos->prefLabel = $class->prefLabel;
        $jskos->broader = count($jskos->ancestors) 
                        ? [ $jskos->ancestors[ count($jskos->ancestors)-1 ] ] 
                        : [];

        $rows = $xpath->query('//table[@class="ddc"]/tr');

        if ($rows->length > count($jskos->ancestors)+1) {
            $jskos->narrower = [];
            foreach ($rows[$rows->length-1]->childNodes as $td) {
                $concept = static::scrapeCell($td, $xpath);
                if ($concept) $jskos->narrower[] = $concept;
            }
        }

        return $jskos;
    }

    public static function scrapeCell($td, $xpath) 
    {
        $nodes = $xpath->query('a',$td);
        if (!$nodes->length) return;
        $notation = $nodes[0]->nodeValue;
        $concept = new Concept([
            'uri' => "http://www.librarything.com/mds/$notation",
            'notation' => [$notation],
        ]);

        $nodes = $xpath->query('div[@class="word"]',$td);
        if ($nodes->length) {
            $label = $nodes[0]->nodeValue;
            $concept->prefLabel = ['en' => $label];
        }

        return $concept;
    }
}
