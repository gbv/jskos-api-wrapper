<?php

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\ConceptScheme;
use JSKOS\Page;
use JSKOS\Error;

const DEBUG = 1; # TODO: use logger instead

function getArray($object, $field) {
    if (isset($object->$field)) {
        $array = $object->$field;
        return is_array($array) ? $array : [$array];
    } else {
        return [];
    }
}

function GFBio2JSKOS($rec) {
    if (empty($rec)) return;

    if (DEBUG) error_log(json_encode($rec, JSON_PRETTY_PRINT));
 
    $jskos = new Concept(['uri'=>$rec->uri]);

    $jskos->notation = getArray($rec,'id');
    $jskos->prefLabel['en'] = $rec->label;

    foreach (getArray($rec, 'synonyms') as $syn) {
        $jskos->altLabel['en'][] = $syn;
    }

    foreach (getArray($rec, 'subClassOf') as $uri) {
        $jskos->broader[] = new Concept(['uri'=> $uri]);
    }

    # TODO: what about field 'has_related_synonym'?

    # TODO: take from config file
    $jskos->inScheme[] = new ConceptScheme([
        'uri' => 'http://bartoc.org/en/node/558',
        'identifier' => [
            'http://www.wikidata.org/entity/Q902623',
            'http://purl.obolibrary.org/obo/chebi.owl'
        ],
        'prefLabel' => [
            'en' => 'Chemical Entities of Biological Interest'
        ],
        'notation' => ['ChEBI']
    ]);

    return $jskos;
}

/**
 * Wraps JSKOS-API to GFBio Terminology Server JSON API
 */
class ChEBIService extends Service {
    
    function query($query) {

        if (!isset($query['uri'])) return;

        $uri = $query['uri'];

        $params = [
            'uri' => $uri,
            'format' => 'json'
        ];
        $url = 'http://terminologies.gfbio.org/api/terminologies/CHEBI/term';
        $url .= '?' . http_build_query($params);
        if (DEBUG) error_log($url);
        $json = @json_decode(@file_get_contents($url));

        if (!isset($json->results)) return;

        $result = [];

        foreach( $json->results as $record) {
            $jskos = GFBio2JSKOS($record);
            if($jskos) $result[] = $jskos;
        }
        
        return new Page($result);
    }
}

?>
