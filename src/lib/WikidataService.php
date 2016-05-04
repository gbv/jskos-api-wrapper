<?php

/**
 * Implements a basic JSKOS concepts endpoint for Wikidata.
 *
 * This wrapper converts Wikidata JSON format to JSKOS.
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';
include_once realpath(__DIR__).'/IDTrait.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class WikidataService extends Service {
    use IDTrait;
    
    protected $supportedParameters = ['notation'];

    /**
     * Query via MediaWikiAPI. TODO: use SPARQL for complex queries
     */
    public function query($query) {
        $id = $this->idFromQuery($query, 
            '/^http:\/\/www\.wikidata\.org\/entity\/([PQ][0-9]+)$/', '/^([PQ][0-9]+)$/');

        if (isset($id)) {
            try {
                $json = @file_get_contents("https://www.wikidata.org/wiki/Special:EntityData/$id.json");
                $data = @json_decode($json);
                $data = $data->entities->{$id};
            } catch (Exception $e) {
                error_log($e);
            }
        }

        if (!isset($data)) {
            return new Page();
        }

        $concept = new Concept(["uri" => "http://www.wikidata.org/entity/$id"]);
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
