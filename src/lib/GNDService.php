<?php

/**
 * Implements a basic JSKOS concepts endpoint for GND.
 *
 * The wrapper converts GND RDF/XML to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;
use JSKOS\RDFMapping;

class GNDService extends Service {
    use IDTrait;
    
    protected $supportedParameters = ['notation'];

    private $rdfMapping;

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        $this->rdfMapping = new RDFMapping(__DIR__.'/GNDMapping.yaml');
        parent::__construct();
    }

    public function query($query) {
        
        $notation = $this->idFromQuery($query, '/^http:\/\/d-nb\.info\/gnd\/([0-9X-]+)$/', '/^[0-9X-]+$/');
        if (!isset($notation)) return;
        
        $uri = "http://d-nb.info/gnd/$notation";
        $jskos = new Concept(['uri'=>$uri, 'notation' => [$notation]]);

        $rdf = RDFMapping::loadRDF("$uri/about/lds", $uri);
        if (!$rdf) return;

        # error_log($rdf->getGraph()->serialise('turtle'));

        $this->rdfMapping->apply($rdf, $jskos); 

        return $jskos;
    }
}
