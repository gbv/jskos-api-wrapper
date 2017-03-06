<?php

/**
 * JSKOS-API Wrapper to Geonames via LOD access via URI.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\RDF\RDFMapping;

class GeonamesService extends JSKOS\RDF\RDFMappingService {
    public static $CONFIG_DIR = __DIR__;

    protected $supportedParameters = ['notation'];

    public function query($query) {
        $jskos = $this->queryUriSpace($query);
        if (!$jskos) return;

        if (substr($jskos->uri,-1) != '/') {
            $jskos->uri = $jskos->uri . '/';
        }

        $rdf = RDFMapping::loadRDF($jskos->uri);
        if (!$rdf) return;
   
        $this->applyRdfMapping($rdf, $jskos); 

        return $jskos;
    }
}
