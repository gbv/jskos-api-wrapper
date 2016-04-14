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
    
    protected $supportedParameters = [];

    public function query($query) {

        if (isset($query['uri']) and
            preg_match('/^http:\/\/bartoc\.org\/en\/node\/([0-9]+)$/', $query['uri'])) {
            $uri = $query['uri'];

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

            return new Page([$scheme]);
        } else {
            return new Page();
        }
    }
}

 
