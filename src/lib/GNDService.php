<?php

/**
 * JSKOS-API Wrapper to GND via LOD access via URI.
 */

include_once realpath(__DIR__.'/../..') . '/vendor/autoload.php';
include_once realpath(__DIR__).'/JSKOSRDFMapping.php';
include_once realpath(__DIR__).'/RDFTrait.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

class GNDService extends Service {
    use RDFTrait;
    
    protected $supportedParameters = ['notation'];

    static $mapping;

    /**
     * Initialize Mapping from YAML file.
     */
    public function __construct() {
        if (!static::$mapping) {
            $file = __DIR__.'/GNDMapping.yaml';
            static::$mapping = new JSKOSRDFMapping($file);
        }
        parent::__construct();
    }

    public function query($query) {

        if (isset($query['uri'])) {
            if (preg_match('/^http:\/\/d-nb\.info\/gnd\/([0-9X-]+)$/', $query['uri'], $match)) {
                $id = $match[1];
            }
        }
            
        if (isset($query['notation'])) {
            if (preg_match('/^[0-9X-]+$/', $query['notation'])) {
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

        $uri = "http://d-nb.info/gnd/$id";
    
        $rdf = new EasyRdf_Graph();
        try {
            // TODO: use newAndLoad($uri);
            $rdf->load("$uri/about/lds","rdfxml");
            $gnd = $rdf->resource($uri);
        } catch (Exception $e){
            // not found or some error at DNB
            return null;
        }

        if ($rdf->isEmpty() or !$gnd) {
            return null;
        }

        $jskos = new Concept(['uri'=>$uri, 'notation' => [$id]]);

        foreach ( $gnd->allResources('owl:sameAs') as $id ) {
            $jskos->identifier[] = "$id";
        }
 
        foreach ( $gnd->typesAsResources() as $type ) {
            $jskos->type[] = (string)$type;
        }

        #error_log($rdf->serialise('turtle'));

        static::$mapping->rdf2jskos($gnd, $jskos); 

        return $jskos;
    }
}
