<?php

/**
 * This wrapper converts VIAF Linked Open Data to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\RDFMapping;
use JSKOS\URISpaceService;
use Symfony\Component\Yaml\Yaml;

class VIAFService extends Service {
    
    protected $supportedParameters = ['notation','search'];

    private $config;
    private $uriSpaceService;
    private $rdfMapping;

    /**
     * Initialize configuration and mapping from YAML file.
     */
    public function __construct() {
        $file = __DIR__.'/VIAFService.yaml';
        $this->config = Yaml::parse(file_get_contents($file));
        $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
        $this->rdfMapping = new RDFMapping($this->config);
        parent::__construct();
    }

    public function query($query) {
        $jskos = $this->uriSpaceService->query($query);
        if ($jskos and $jskos->uri) {
            return $this->lookupEntity($jskos->uri);
        } elseif (isset($query['search'])) {
            return new Page( $this->search($query['search']) );
        }
    }

    public function lookupEntity($uri) {
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
