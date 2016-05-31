<?php

/**
 * JSKOS-API Wrapper to Geonames via LOD access via URI.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;
use JSKOS\RDFMapping;

class GeonamesService extends Service {
    use IDTrait;

    protected $supportedParameters = ['notation'];

    private $rdfMapping;

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        $this->rdfMapping = new RDFMapping(__DIR__.'/GeonamesMapping.yaml');
        parent::__construct();
    }

    /**
     * Perform query.
     */ 
    public function query($query) {

        $id = $this->idFromQuery($query, '/^http:\/\/sws\.geonames\.org\/([0-9]+)\/$/', '/^[0-9]+$/');
        if (!isset($id)) return;
        $uri = $this->rdfMapping->buildUri($id);

        $rdf = RDFMapping::loadRDF($uri);
        if (!$rdf) return;

        $jskos = new Concept([ 'uri' => $uri, 'notation' => [ $id ]]);

        // TODO: get childrenFeatures if requested
        // TODO: modified, created, license
    
        $this->rdfMapping->apply($rdf, $jskos); 
 
        return $jskos;
    }
}
