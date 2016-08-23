<?php

/**
 * Implements a basic JSKOS concepts endpoint for GND.
 *
 * The wrapper converts GND RDF/XML to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;
use JSKOS\RDFMapping;
use JSKOS\URISpaceService;
use Symfony\Component\Yaml\Yaml;

class GNDService extends Service
{
    
    protected $supportedParameters = ['notation'];

    private $config;
    private $uriSpaceService;
    private $rdfMapping;

    /**
     * Initialize configuration and mapping from YAML file.
     */
    public function __construct() {
        $file = __DIR__.'/GNDService.yaml';
        $this->config = Yaml::parse(file_get_contents($file));
        $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
        $this->rdfMapping = new RDFMapping($this->config);
        parent::__construct();
    }

    /**
     * Perform entity lookup query.
     */
    public function query($query) {
        $jskos = $this->uriSpaceService->query($query);
        if (!$jskos) return;

        $rdf = RDFMapping::loadRDF($jskos->uri ."/about/lds", $jskos->uri);
        if (!$rdf) return;

        # TODO: fix date format
        # error_log($rdf->getGraph()->serialise('turtle'));

        $this->rdfMapping->apply($rdf, $jskos); 

        return $jskos;
    }
}
