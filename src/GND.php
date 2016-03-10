<?php

/**
 * Implements a basic JSKOS concepts endpoint for GND.
 *
 * This wrapper converts GND RDF/XML to JSKOS.
 *
 * @package JSKOS
 */

include realpath(__DIR__) . '/lib/GNDService.php';

\JSKOS\Server::runService(new GNDService());

/*
   $concept = new Concept(["uri" => "$uri"]);

    foreach (getResources($xml, 'foaf:page') as $page) {
        $concept->subjectOf = [ "uri" => $page ];
    }

    foreach (getResources($xml, 'gndo:broaderTermGeneral') as $uri) {
        $concept->broader[] = [ "uri" => $uri ];
    }

    foreach (getLiterals($xml, 'gndo:preferredNameForTheSubjectHeading') as $label) {
        $concept->prefLabel["de"] = $label;
    }
*/

