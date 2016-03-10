<?php

/**
 * JSKOS-API Wrapper to GND via LOD access via URI.
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

EasyRdf_Namespace::set('gnd','http://d-nb.info/standards/elementset/gnd#');

class GNDService extends Service {

static $GNDOntology2JSKOS = [
    'broader' => [
        'type' => 'URI',
        'properties' => [
            'gnd:broaderTermPartitive'
        ]
    ],
    'related' => [
        'type' => 'URI',
        'properties' => [
            'gnd:accordingWork',
            'gnd:acquaintanceshipOrFriendship',
        ]
    ],
    'relatedPlace' => [
        'type' => 'URI',
        'properties' => [
            'gnd:associatedPlace',
            'gnd:characteristicPlace',
            'gnd:otherPlace',
            'gnd:spatialAreaOfActivity',
            'gnd:relatedPlaceOrGeographicName',
            'gnd:place'
        ],
    ],
    'prefLabel' => [
        'type' => 'literal',
        'unique' => true,
        'properties' => [
            'gnd:preferredName',
            'gnd:preferredNameForThePerson',
            'gnd:preferredNameForTheConferenceOrEvent',
            'gnd:preferredNameForThePlaceOrGeographicName',
            'gnd:preferredNameForTheFamily',
            'gnd:preferredNameForTheSubjectHeading',
            'gnd:preferredNameForTheCorporateBody',
            'gnd:preferredNameForTheWork',
        ],
    ],
    'scopeNote' => [
        'type' => 'literal',
        'properties' => [
            'gnd:biographicalOrHistoricalInformation'
        ]
    ],
    'definition' => [
        'type' => 'literal',
        'properties' => ['gnd:definition']
    ],
    'relatedDate' => [
        'type' => 'datatype',
        'properties' => [
            'gnd:associatedDate',
            'gnd:dateOfDiscovery',
            'gnd:periodOfActivity',
        ]
    ]

];

    
    protected $supportedParameters = ['notation'];

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


        foreach (static::$GNDOntology2JSKOS as $property => $mapping)
        {
            $type = $mapping['type'];

            foreach ( $mapping['properties'] as $gndProperty ) {

                if ($type == 'URI') 
                { 
                    foreach ( $gnd->allResources($gndProperty) as $resource ) {
                        if (!isset($jskos->$property)) {
                            $jskos->$property = [];
                        }
                        array_push( $jskos->$property, ['uri' => (string)$resource] );
                    }

                } elseif ($type == 'literal')
                {
                    foreach ( $gnd->allLiterals($gndProperty) as $literal ) {
                        $value = (string)$literal;
                        $language = $literal->getLang() ? $literal->getLang() : "de";
                    
                        $languageMap = isset($jskos->$property) ? $jskos->$property : [];

                        if (isset($mapping['unique'])) {
                            $languageMap[$language] = $value;
                        } else {
                            $languageMap[$language][] = $value;
                        }

                        $jskos->$property = $languageMap;
                    }
                } elseif ($type == 'datatype') {
                    foreach ( $gnd->allLiterals($gndProperty) as $literal ) {
                        $value = (string)$literal;
                        if (!isset($jskos->$property)) {
                            $jskos->$property = [];
                        }
                        array_push( $jskos->$property, $value );
                    }
                }
            }
        }

        return $jskos;
    }
}
