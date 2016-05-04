<?php

/**
 * JSKOS-API Wrapper to Geonames via LOD access via URI.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class GeonamesService extends Service {
    use RDFTrait;
    use IDTrait;

    protected $supportedParameters = ['notation'];

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        $this->loadMapping(__DIR__.'/GeonamesMapping.yaml');
        parent::__construct();
    }

    /**
     * Perform query.
     */ 
    public function query($query) {

        $id = $this->idFromQuery($query, '/^http:\/\/sws\.geonames\.org\/([0-9]+)\/$/', '/^[0-9]+$/');
        $jskos = null;

        // get concept by notation and/or uri
        if (isset($id)) {
            $uri = "http://sws.geonames.org/$id/";

            $rdf = $this->loadRDF($uri);
            if (!$rdf) return;

            $jskos = new Concept([ 'uri' => $uri, 'notation' => [ $id ]]);

            // TODO: get childrenFeatures if requested
            // TODO: modified, created, license
        
            $this->rdf2jskos($rdf, $jskos); 
        }
 
        return $jskos;
    }
}
