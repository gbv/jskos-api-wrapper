<?php

/**
 * Implements a basic JSKOS concepts endpoint for DDC 
 * by wrapping Norsk WebDewey (Skosmos instance).
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Concept;
use JSKOS\RDF\RDFMapping;

class DDCService extends JSKOS\RDF\RDFMappingService {
    public static $CONFIG_DIR = __DIR__;
    
    protected $supportedParameters = ['notation'];

    public function query($query) {

        $jskos = $this->queryUriSpace($query);
        if (!$jskos) return;

        // clean up notation and URI
        // FIXME: what if both uri and notation are queried?
        $notation = $jskos->notation[0];
        if ( substr($jskos->uri,-5) == '/e23/') {
            if ( strpos($jskos->uri, 'T') ) {
                return;
            }
            # $notation = preg_replace('!/e23/$!','',$notation);
            # if ( strpos($notation,'--') ) $notation = 'T'.$notation;
            # $jskos->notation[0] = $notation;
        } else {
            $jskos->uri = preg_replace('/T/','',$jskos->uri);
            $jskos->uri .= '/e23/';
        }

        $jskos->notation = []; // get via LOD

        $url = "http://data.ub.uio.no/skosmos/rest/v1/ddc/data?"
             . http_build_query([
                 'uri' => $jskos->uri,
                 'format' => 'text/turtle'
            ]);        
        error_log($url);
        
        # FIXME: timeouts
        $rdf = RDFMapping::loadRDF($url, $jskos->uri, 'turtle');
        if (!$rdf or $rdf->getGraph()->countTriples() < 3) return;


        $this->applyRdfMapping($rdf, $jskos); 

        // add synthesized number components
        foreach ( $rdf->allResources('mads:componentList') as $composed ) {
            foreach ( $composed as $c ) {
                # FIXME: set-insert for jskos-broader
                $uri = $c->getUri();
                $dup = false;
                foreach ($jskos->broader as $b) {
                    if ($b->uri == $uri) $dup = true;
                }
                if (!$dup) $jskos->broader[] = new Concept(['uri' => $uri]);
            }
        }        

        // add DDC specific types
        $notation = $jskos->notation[0];
        if ( preg_match('/T([1-6A-C]+)--/', $notation) ) {
            $jskos->type[] = 'http://dewey.info/type/TableEntry';
        }
        if ( preg_match('/-[^-]/', $notation) ) {
            $jskos->type[] = 'http://dewey.info/type/NumberSpan';
        }

        $jskos->inScheme[] = ['uri'=>'http://dewey.info/scheme/ddc/'];

        return $jskos;
    }
}
