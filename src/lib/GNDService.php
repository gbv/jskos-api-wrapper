<?php

/**
 * Implements a basic JSKOS concepts endpoint for GND.
 *
 * The wrapper converts GND RDF/XML to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\RDF\RDFMapping;

class GNDService extends JSKOS\RDF\RDFMappingService {
    public static $CONFIG_DIR = __DIR__;
    
    protected $supportedParameters = ['notation'];

    public function query($query) {
        $jskos = $this->queryUriSpace($query);
        if (!$jskos) return;

        $rdf = RDFMapping::loadRDF($jskos->uri ."/about/lds", $jskos->uri);
        if (!$rdf) return;

        # TODO: fix date format
        # error_log($rdf->getGraph()->serialise('turtle'));

        $this->applyRdfMapping($rdf, $jskos); 

        return $jskos;
    }
}
