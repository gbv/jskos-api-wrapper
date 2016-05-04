<?php

/**
 * Draft of a JSKOS wrapper to Europeana OpenSKOS API.
 * Converts JSON-LD of Europeana EDM to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\ConceptScheme;
use JSKOS\Page;
use JSKOS\Error;

const DEBUG = 0; # TODO: use logger instead

function EDM2JSKOS($edm) {
    if (empty($edm)) return;

    if (DEBUG) error_log(json_encode($edm, JSON_PRETTY_PRINT));
    
    // e.g. http://id.loc.gov/authorities/subjects/sh2007003224
    if ($edm->class == 'Concept') {
        $jskos = new Concept();
        foreach ($edm->inScheme as $scheme) {
            $jskos->inScheme[] = [ "uri" => $scheme ];
        }
    }
    // e.g. http://data.europeana.eu/concept/loc
    elseif ($edm->class == 'ConceptScheme') {
        $jskos = new ConceptScheme();
        if (isset($edm->dcterms_title)) {
            // no language tag, so we assume English
            $jskos->prefLabel['en'] = $edm->dcterms_title[0];
        }
    } else {
        return;
    }

    // common fields
    $jskos->uri = $edm->uri;

    if (isset($edm->notation)) {
        $jskos->notation = $edm->notation; // must be array!
    }

    foreach (array_keys(get_object_vars($edm)) as $key) {
        if ( preg_match('/^prefLabel@(.+)$/', $key, $match) ) {
            $jskos->prefLabel[$match[1]] = $edm->{$key}[0];
        }
    }

    return $jskos; 
}

/**
 * Wrap JSKOS-API request to OpenSKOS API request and response.
 */
class OpenSKOSService extends Service {
    use LuceneTrait;
    
    protected $supportedParameters = ['notation'];

    function query($query) {

        $params = [];  // OpenSKOS API parameter
        $result = [];  // list of JSKOS objects

        if (isset($query['uri'])) {
            $params['id'] = $query['uri'];
        }

        if (isset($query['notation'])) {
            $params['q'] = $this->luceneQuery('notation',$query['notation']);
        }

        if (empty($params)) {
            return new Page();
        }

        # get entity via OpenSKOS API
        $params['format'] = 'json';
        $url = 'http://skos.europeana.eu/api/find-concepts';
        $url .= '?' . http_build_query($params);
        if (DEBUG) error_log($url);
        $json = @json_decode(@file_get_contents($url));

        // multiple records or single record result set?
        $records = isset($json->response)
                 ? $json->response->docs : [ $json ];

        foreach( $records as $edm) {
            $jskos = EDM2JSKOS($edm);
            if($jskos) $result[] = $jskos;
        }
        
        return new Page($result);
    }

}

