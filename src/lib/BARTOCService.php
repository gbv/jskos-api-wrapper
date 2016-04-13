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

            return new Page([$scheme]);
        } else {
            return new Page();
        }
    }
}

 
