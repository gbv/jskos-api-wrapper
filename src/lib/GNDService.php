<?php

/**
 * Implements a basic JSKOS concepts endpoint for GND.
 *
 * The wrapper converts GND RDF/XML to JSKOS.
 */

include_once realpath(__DIR__.'/../..') . '/vendor/autoload.php';
include_once realpath(__DIR__).'/RDFTrait.php';
include_once realpath(__DIR__).'/IDTrait.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class GNDService extends Service {
    use RDFTrait;
    use IDtrait;
    
    protected $supportedParameters = ['notation'];

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        $this->loadMapping(__DIR__.'/GNDMapping.yaml');
        parent::__construct();
    }

    public function query($query) {
        
        $id = $this->idFromQuery($query, '/^http:\/\/d-nb\.info\/gnd\/([0-9X-]+)$/', '/^[0-9X-]+$/');

        if (isset($id)) {
            $uri = "http://d-nb.info/gnd/$id";
        } else {
            return;
        }
    
        # error_log("$uri");

        $rdf = $this->loadRDF("$uri/about/lds", $uri, "rdfxml");
        if (!$rdf) return;

        # error_log($rdf->getGraph()->serialise('turtle'));

        $jskos = new Concept(['uri'=>$uri, 'notation' => [$id]]);

        foreach ( $rdf->allResources('owl:sameAs') as $id ) {
            $jskos->identifier[] = "$id";
        }
 
        foreach ( $rdf->typesAsResources() as $type ) {
            $jskos->type[] = (string)$type;
        }

        $this->rdf2jskos($rdf, $jskos, 'de'); 

        return $jskos;
    }
}
