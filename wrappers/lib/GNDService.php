<?php

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\Page;
use JSKOS\Error;

EasyRdf_Namespace::set('gnd','http://d-nb.info/standards/elementset/gnd#');

class GNDService extends Service {
    
    protected $supportedParameters = ['notation'];

    public function query($query) {

        if (isset($query['uri'])) {
            if (preg_match('/^http:\/\/www\.d-nb\.info\/gnd\/([0-9X-]+)$/', $query['uri'], $match)) {
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

        $prefLabel = [
            'preferredName',
            'preferredNameForThePerson', // ...
            'preferredNameForTheConferenceOrEvent', // ?
            'preferredNameForThePlaceOrGeographicName',
            'preferredNameForTheFamily',
            'preferredNameForTheSubjectHeading',
            'preferredNameForTheCorporateBody',
            'preferredNameForTheWork',
        ];
        foreach ( $prefLabel as $property ) {
            foreach ( $gnd->allLiterals("gnd:$property") as $literal ) {
                $jskos->prefLabel['de'] = (string)$literal;
            }
        }   

        $scopeNote = ['biographicalOrHistoricalInformation'];
        foreach ( $scopeNote as $property ) {
            foreach ( $gnd->allLiterals("gnd:$property") as $literal ) {
                $jskos->scopeNote['de'][] = (string)$literal;
            }
        }   

        $related = ['accordingWork','acquaintanceshipOrFriendship'];
        foreach ( $related as $property ) {
            foreach ( $gnd->allResources("gnd:$property") as $resource ) {
                $jskos->related[] = ['uri' => (string)$resource];
            }
        }
       
        $relatedDate = ['associatedDate','dateOfDiscovery','periodOfActivity'];
        foreach ( $relatedDate as $property ) {
            foreach ( $gnd->allLiterals("gnd:$property") as $literal ) {
                $jskos->relatedDate[] = (string)$literal;
            }
        }   

        $relatedPlace = [
            'associatedPlace','characteristicPlace','otherPlace',
            'spatialAreaOfActivity',
            'place'
        ]; // TODO: placeOf...
        foreach ( $relatedPlace as $property ) {
            foreach ( $gnd->allResources("gnd:$property") as $resource ) {
                $jskos->relatedPlace[] = ["uri"=> (string)$resource];
            }
        }   

        #error_log($rdf->serialise('turtle'));

        return $jskos;
    }
}
