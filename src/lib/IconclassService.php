<?php

/**
 * This wrapper converts Iconclass Linked Open Data to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Concept;
use JSKOS\ConceptScheme;
use JSKOS\RDFMapping;

class IconclassService extends JSKOS\RDFBasedService {
    public static $CONFIG_DIR = __DIR__;
 
    protected $supportedParameters = ['notation'];

    public function query($query) {
        # TODO: rawurldecode

        if (isset($query['uri']) and 
            preg_match('/^http:\/\/iconclass\.org\/(.+)$/', $query['uri'], $match)) {
            $notation = rawurldecode($match[1]);
        }

        if (isset($query['notation']) and $query['notation'] != "") {
            if (isset($notation) and $notation != $query['notation']) {
                return;
            } else {
                $notation = $query['notation'];
            }
        }

        if (!isset($notation)) return;

        $uri = "http://iconclass.org/".rawurlencode($notation);

        $rdf = RDFMapping::loadRDF("$uri.rdf", $uri, "rdfxml");
        if (!$rdf or $rdf->getGraph()->countTriples() < 3) return;

        $jskos = new Concept([ 'uri' => $uri, 'notation' => [$notation] ]);
        $this->applyRdfMapping($rdf, $jskos); 

        $jskos->inScheme[] = new ConceptScheme([
          'uri' => 'http://bartoc.org/en/node/459' 
        ]);

        return $jskos;
    }
}
