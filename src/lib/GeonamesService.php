<?php

/**
 * JSKOS-API Wrapper to Geonames via LOD access via URI.
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

EasyRdf_Namespace::set('gn','http://www.geonames.org/ontology#');

include realpath(__DIR__).'/JSKOSRDFMapping.php';

class GeonamesService extends Service {

    protected $supportedParameters = ['notation'];
    static $mapping;

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
    
        try { 
            $rdf = EasyRdf_Graph::newAndLoad($uri); 
            $rdf = $rdf->resource($uri);
            if (!$rdf) {
                return;
            } 
        } catch( Exception $e ) {
            return;
        }

        $jskos = new Concept([ 'uri' => $uri, 'notation' => [ $id ]]);

        // TODO: childrenFeatures
        // TODO: modified, created, license
        
        # TODO: read mapping from file instead
        if (!static::$mapping) {
            static::$mapping = new JSKOSRDFMapping([
    'broader' => [
        'type' => 'URI',
        'properties' => [
            'gn:parentFeature'
        ]
    ],
    'prefLabel' => [
        'type' => 'literal',
        'unique' => true,
        'properties' => [
            'gn:officialName',
        ],
    ],
    'altLabel' => [
        'type' => 'literal',
        'properties' => [
            'gn:alternateName',
        ],
    ],
    'notation' => [
        'type' => 'datatype',
        'properties' => [
            'gn:countryCode',
        ],
    ],
    # TODO: should better be list of positions
/* 
    'latitude' => [
        'type' => 'literal',
        'properties' => [ 'wgs84_pos:lat' ],
    ],
    'longitude' => [
        'type' => 'literal',
        'properties' => [ 'wgs84_pos:long' ],
    ]
*/
]);
        }

        static::$mapping->rdf2jskos( $rdf, $jskos ); 

        return $jskos;
    }
}
