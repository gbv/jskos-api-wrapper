<?php

/**
 * Basic JSKOS concept schemes endpoint for BARTOC.org.
 */

include_once __DIR__.'/../../vendor/autoload.php';

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
    use IDTrait;
    
    protected $supportedParameters = ['notation','search'];

    public function query($query) {
        $id = $this->idFromQuery($query, '/^http:\/\/bartoc\.org\/en\/node\/([0-9]+)$/', '/^[0-9]+$/');
        if (isset($id)) {
            return $this->lookupByURI("http://bartoc.org/en/node/$id");
        } elseif (isset($query['search'])) {
            return new Page( $this->search($query['search']) );
        } else {
            return;
        }
    }

    public function lookupByURI($uri) {
        $rdf = RDFMapper::loadRDF($uri);
        if (!$rdf) return;

        $scheme = new ConceptScheme(['uri' => $uri]);

        # TODO: use RDF mapping file from YAML instead

        # url
        foreach ( getUris($rdf, 'foaf:page') as $url ) {
            if (substr($url,0,26) != 'http://bartoc.org/en/node/') {
                $scheme->url = $url;
            }
        }

        # Wikidata item
        foreach ( getUris($rdf, 'dc:relation', '/^http:\/\/www\.wikidata\.org\/entity\/Q[0-9]+$/') as $uri ) {
            $scheme->identifier = [ $uri ];
        }

        # prefLabel and notation (FIXME: language is not always English)
        foreach ($rdf->allLiterals('schema:name') as $name) {
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

 
