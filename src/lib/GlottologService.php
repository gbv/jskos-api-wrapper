<?php

/**
 * JSKOS-API Wrapper to Glottolog.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\RDF\RDFMapping;

class GlottologService extends JSKOS\RDF\RDFMappingService {
    public static $CONFIG_DIR = __DIR__;

    protected $supportedParameters = ['notation','uri','search'];

    public function query($query) {
        $jskos = $this->queryURISpace($query);
        if (!$jskos) return;

        $rdf = RDFMapping::loadRDF($jskos->uri.'.ttl', $jskos->uri, 'turtle');
        if (!$rdf) return;
   
        $this->applyRDFMapping($rdf, $jskos); 
        unset($jskos->altLabel['x-clld']);

        return $jskos;
    }
}

