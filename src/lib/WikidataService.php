<?php

/**
 * Implements a basic JSKOS concepts endpoint for Wikidata.
 *
 * This wrapper converts Wikidata JSON format to JSKOS.
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;
use JSKOS\URISpaceService;
use Symfony\Component\Yaml\Yaml;

class WikidataService extends Service {
    
    protected $supportedParameters = ['notation'];

    private $uriSpaceService;

    public function __construct() {
        $file = __DIR__.'/WikidataService.yaml';
        $this->config = Yaml::parse(file_get_contents($file));
        $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
    }

    /**
     * Query via MediaWikiAPI. TODO: use SPARQL for complex queries
     */
    public function query($query) {
        $concept = $this->uriSpaceService->query($query);
        if (!$concept) return;
        
        try {
            $url = "https://www.wikidata.org/wiki/Special:EntityData/"
                 . $concept->notation[0] . ".json";
            $json = @file_get_contents($url);
            $data = @json_decode($json);
            $data = $data->entities->{$concept->notation[0]};
        } catch (Exception $e) {
            error_log($e);
        }
        if (!isset($data)) return;

        $concept->modified = $data->modified;
        
        foreach ($data->labels as $language => $value) {
            $concept->prefLabel[$language] = $value->value;
        }

        foreach ($data->descriptions as $language => $value) {
            $concept->scopeNote[$language] = $value->value;
        }

        foreach ($data->aliases as $language => $values) {
            foreach ($values as $value) {
                $concept->altLabel[$language][] = $value->value;
            }
        }
        
        # TODO: type (item or property) wikibase:Item / wikibase:Property

        # TODO: more claims

        # depiction
        if (isset($data->claims->P18)) {
            # TODO: only use "truthy" statements
            foreach ($data->claims->P18 as $statement) {
                $snak = $statement->mainsnak;
                if ($snak->datatype == "commonsMedia") {
                    $concept->depiction = "http://commons.wikimedia.org/wiki/Special:FilePath/"
                        . rawurlencode($snak->datavalue->value);
                }
            }
        }

        # subclass of (TODO: reverse)
        if (isset($data->claims->P279)) {
            foreach ($data->claims->P279 as $statement) {
                $snak = $statement->mainsnak;
                if ($snak->datatype == "wikibase-item") {
                    $id = $snak->datavalue->value->{'numeric-id'};
                    $concept->broader[] = new Concept(["uri" => "http://www.wikidata.org/entity/Q$id"]);
                }
            }
        }

        # TODO: sitelinks

        return $concept;
    }

}
