<?php

namespace JSKOS;

use Symfony\Component\Yaml\Yaml;

class RDFBasedService extends ConfiguredService {
    private $rdfMapping;

    public function __construct() {
        parent::__construct(); // provides config and uriSpace
        $this->rdfMapping = new RDFMapping($this->config);
    }

    public function applyRDFMapping($rdf, $jskos) {
        $this->rdfMapping->apply($rdf, $jskos);
    }
}
