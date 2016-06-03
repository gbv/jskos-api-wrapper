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
use JSKOS\URISpaceService;
use Symfony\Component\Yaml\Yaml;

class GeonamesService extends Service {

    protected $supportedParameters = ['notation'];

    private $config;
    private $uriSpaceService;
    private $rdfMapping;

    /**
     * Initialize configuration and mapping from YAML file.
     */
    public function __construct() {
        $file = __DIR__.'/GeonamesService.yaml';
        $this->config = Yaml::parse(file_get_contents($file));
        $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
        $this->rdfMapping = new RDFMapping($this->config);
        parent::__construct();
    }

    /**
     * Perform query.
     */ 
    public function query($query) {
        $jskos = $this->uriSpaceService->query($query);
        if (!$jskos) return;

        if (substr($jskos->uri,-1) != '/') {
            $jskos->uri = $jskos->uri . '/';
        }

        $rdf = RDFMapping::loadRDF($jskos->uri);
        if (!$rdf) return;

        // TODO: get childrenFeatures if requested
        // TODO: modified, created, license
    
        $this->rdfMapping->apply($rdf, $jskos); 
 
        return $jskos;
    }
}
