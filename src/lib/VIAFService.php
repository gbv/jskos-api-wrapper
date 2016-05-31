<?php

/**
 * This wrapper converts VIAF Linked Open Data to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\RDFMapping;

class VIAFService extends Service {
    use IDTrait;
    
    protected $supportedParameters = ['notation','search'];

    private $rdfMapping;

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        $this->rdfMapping = new RDFMapping(__DIR__.'/VIAFMapping.yaml');
        parent::__construct();
    }

    public function query($query) {
        $notation = $this->idFromQuery($query, '/^http:\/\/viaf\.org\/viaf\/([0-9]+)$/', '/^[0-9]+$/');
        if (isset($notation)) {
            return $this->lookup( $this->rdfMapping->buildUri($notation) );
        } elseif (isset($query['search'])) {
            return new Page( $this->search($query['search']) );
        }
    }

    public function lookup($uri) {
        $rdf = RDFMapping::loadRDF($uri);
        if (!$rdf) return;
        # error_log($rdf->getGraph()->serialise('turtle'));

        $jskos = new Concept([ 'uri' => $uri ]);
        $this->rdfMapping->apply($rdf, $jskos); 

        return $jskos;
    }

    private function search($search) {
        $url = 'http://www.viaf.org/viaf/AutoSuggest?' . http_build_query(['query'=>$search]);
        try {
            $json = @json_decode( @file_get_contents($url) );
            # query = $json['query']
            foreach ( $json->result as $hit ) {
                $response[] = new Concept([
                    # TODO: $hit->term / $hit->displayform contains search but not prefLabel!
                    'uri' => "http://viaf.org/viaf/".$hit->viafid,
                ]);
            }
            return $response;
        } catch (Exception $e) {
            error_log($e);
            return [];
        }
    } 
}
