<?php

/**
 * This wrapper converts Iconclass Linked Open Data to JSKOS.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\RDFMapping;
use JSKOS\URISpaceService;
use Symfony\Component\Yaml\Yaml;

class IconclassService extends Service {
    
    protected $supportedParameters = ['notation'];

    private $config;
    private $uriSpaceService;
    private $rdfMapping;

    /**
     * Initialize configuration and mapping from YAML file.
     */
    public function __construct() {
        $file = __DIR__.'/IconclassService.yaml';
        $this->config = Yaml::parse(file_get_contents($file));
        $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
        $this->rdfMapping = new RDFMapping($this->config);
        parent::__construct();
    }

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
        $this->rdfMapping->apply($rdf, $jskos); 

        return $jskos;
    }
}
