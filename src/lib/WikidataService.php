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

class WikidataService extends JSKOS\ConfiguredService {
    public static $CONFIG_DIR = __DIR__;
    
    protected $supportedParameters = ['notation','uri'];

    /**
     * Query via MediaWikiAPI. TODO: use SPARQL for complex queries
     */
    public function query($query) {
        $concept = $this->queryUriSpace($query);
        if (!$concept) return;
        
        try {
            $url = "https://www.wikidata.org/wiki/Special:EntityData/"
                 . $concept->notation[0] . ".json?cache=2";
            # TODO: avoid caching?
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

        // TODO: get in other languages as well
        foreach ($data->descriptions as $language => $value) {
            $concept->scopeNote[$language][] = $value->value;
        }

        foreach ($data->aliases as $language => $values) {
            foreach ($values as $value) {
                $concept->altLabel[$language][] = $value->value;
            }
        }
        
        # TODO: type (item or property) wikibase:Item / wikibase:Property

        # depiction
        $depictions = static::mainsnakValues($data, 'P18', 'commonsMedia');
        foreach ($depictions as $img) {
            $concept->depiction[] = "http://commons.wikimedia.org/wiki/Special:FilePath/"
                                  . rawurlencode($img);
        }

        # homepage
        $urls = static::mainsnakValues($data, 'P856', 'url');
        if (count($urls)) { 
            $concept->url = $urls[0];
        }

        # startDate
        foreach (['P569','P571','P580'] as $p) {
            $date = static::mainsnakValues($data, $p, 'time', 'time');
            if (count($date)) {
                $concept->startDate = preg_replace('/^\+/','',$date[0]);
                break;
            }
        }

        # endDate
        foreach (['P570','P576','P582'] as $p) {
            $date = static::mainsnakValues($data, $p, 'time', 'time');
            if (count($date)) {
                $concept->endDate = preg_replace('/^\+/','',$date[0]);
                break;
            }
        }

        # TODO: more claims
        static::mapItemClaims($data, $concept, 'P279', 'broader'); // subclass of
        static::mapItemClaims($data, $concept, 'P131', 'broader'); // administrative territorial entity
        static::mapItemClaims($data, $concept, 'P155', 'previous');
        static::mapItemClaims($data, $concept, 'P156', 'next');

        # TODO: sitelinks

        return $concept;
    }

    static function mainsnakValues( $item, $property, $datatype=null, $valuefield=null ) {
        $values = [];
        if (isset($item->claims->$property)) {
            foreach ($item->claims->$property as $statement) {
                $mainsnak = $statement->mainsnak;
                if (!$datatype) {
                    error_log(print_r($mainsnak,1));
                    continue;
                }
                if ($mainsnak->datatype == $datatype) {
                    $value = $mainsnak->datavalue->value;
                    $values[] = $valuefield ? $value->{$valuefield} : $value;
                }
            }
        }
        return $values;
    }
 
    static function mapItemClaims( $item, $concept, $pid, $field ) {
        $values = static::mainsnakValues( $item, $pid, 'wikibase-item', 'id' );
        foreach ($values as $id) {
            $set = $concept->$field;
            $set[] = new Concept(["uri" => "http://www.wikidata.org/entity/$id"]);
            $concept->$field = $set;
        }
    }
}
