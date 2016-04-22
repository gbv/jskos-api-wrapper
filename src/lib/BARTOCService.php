<?php
/**
 * Implements a basic JSKOS concept schemes endpoint for BARTOC.
 * @package JSKOS
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use JSKOS\Service;
use JSKOS\ConceptScheme;
use JSKOS\Page;
use JSKOS\Error;

# helper function
function getUris( $subject, $predicate, $pattern = null ) {
    $uris = [];
    foreach ( $subject->allResources($predicate) as $object ) {
        if ( !$object->isBNode() ) {
            $object = $object->getUri();
            if ( !$pattern or preg_match($pattern, $object) ) {
                $uris[] = $object;
            }
        }
    }
    return $uris;
}

class BARTOCService extends Service {
    
    protected $supportedParameters = ['search'];

    public function query($query) {
        if (isset($query['uri'])) {
            return $this->lookupByURI($query['uri']);
        } elseif (isset($query['search'])) {
            return new Page( $this->search($query['search']) );
        }
    }

    public function lookupByURI($uri) {
        if ( !preg_match('/^http:\/\/bartoc\.org\/en\/node\/([0-9]+)$/', $uri) ) {
            return;
        }

        try { 
            $rdf = EasyRdf_Graph::newAndLoad($uri); 
            $bartoc = $rdf->resource($uri);
            if (!$bartoc) {
                return;
            } 
        } catch( Exception $e ) {
            return;
        }

        $scheme = new ConceptScheme(['uri' => $uri]);

        # url
        foreach ( getUris($bartoc, 'foaf:page') as $url ) {
            if (substr($url,0,26) != 'http://bartoc.org/en/node/') {
                $scheme->url = $url;
            }
        }

        # Wikidata item
        foreach ( getUris($bartoc, 'dc:relation', '/^http:\/\/www\.wikidata\.org\/entity\/Q[0-9]+$/') as $uri ) {
            $scheme->identifier = [ $uri ];
        }

        # prefLabel and notation (FIXME: language is not always English)
        foreach ($bartoc->allLiterals('schema:name') as $name) {
            $name = $name->getValue();
            if (preg_match('/^[A-Z]{2,5}$/', $name)) {
                $scheme->notation = [ $name ];
            } else {
                $scheme->prefLabel = [ 'en' => $name ];
            }
        }

        # FIXME: schema:about in RDFa output of BARTOC is malformed!

        return $scheme;
    }

    /**
     * Basic search as proof of concept. Of little use without a Drupal search API.
     */
    private function search($search) {
        $query = 'en/autocomplete_filter/title/title_finder/page/0/'.$search;
        $url = 'http://bartoc.org/index.php?' . http_build_query(['q'=>$query]);
        try {
            $json = @json_decode( @file_get_contents($url) );
            $schemas = [];
            foreach ( $json as $key => $value ) {
                # unfortunately IDs are not included in the result!
                $schemas[] = new ConceptScheme(['prefLabel' => ['en' => $key]]);
            }
            return $schemas;
        } catch (Exception $e) {
            error_log($e);
            return [];
        }
    } 
}

 
