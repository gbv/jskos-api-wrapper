<?php

/**
 * JSKOS-API Wrapper to Geonames via LOD access via URI.
 */

include_once realpath(__DIR__.'/../..') . '/vendor/autoload.php';
include_once realpath(__DIR__).'/JSKOSRDFMapping.php';
include_once realpath(__DIR__).'/RDFTrait.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class GeonamesService extends Service {
    use RDFTrait;

    protected $supportedParameters = ['notation'];

    static $mapping;

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        # TODO: move into trait:
        # $this->setMapping(__DIR__.'/GeonamesMapping.yaml');
        if (!static::$mapping) {
            $file = __DIR__.'/GeonamesMapping.yaml';
            static::$mapping = new JSKOSRDFMapping($file);
        }
        parent::__construct();
    }

    /**
     * Perform query.
     */ 
    public function query($query) {

        if (isset($query['uri'])) {
            if (preg_match('/^http:\/\/sws\.geonames\.org\/([0-9]+)\/$/', $query['uri'], $match)) {
                $id = $match[1];
            }
        }
            
        if (isset($query['notation'])) {
            if (preg_match('/^[0-9]+$/', $query['notation'])) {
                $notation = strtoupper($query['notation']);
                if (isset($id) and $id != $notation) {
                    unset($id);
                } else {
                    $id = $notation;
                }
            }
        }

        if (!isset($id)) {
            return null;
        }

        $uri = "http://sws.geonames.org/$id/";
    
        $rdf = $this->loadRDF($uri);
        if (!$rdf) {
            return;
        }

        $jskos = new Concept([ 'uri' => $uri, 'notation' => [ $id ]]);

        // TODO: get childrenFeatures if requested
        // TODO: modified, created, license
        
        static::$mapping->rdf2jskos($rdf, $jskos); 

        return $jskos;
    }
}
