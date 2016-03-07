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
function getResources($xml, $xpath)
{
    $resources = [];
    foreach ($xml->xpath($xpath) as $node) {
        if (isset($node->attributes('rdf', true)['resource'])) {
            $resources[] = (string)$node->attributes('rdf', true)['resource'];
        }
    }
    return $resources;
}

function getLiterals($xml, $xpath)
{
    $literals = [];
    foreach ($xml->xpath($xpath) as $node) {
        $string = $node->__toString();
        if ($string != "") {
            $literals[] = $string;
        }
    }
    return $literals;
}

function GNDWrapper($query)
{
    if (isset($id)) {
        $uri = "http://d-nb.info/gnd/$id";
        try {
            # TODO: use content negotation and EasyRDF instead
            $xml = simplexml_load_file("$uri/about/lds");
            $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $xml->registerXPathNamespace('foaf', 'http://xmlns.com/foaf/0.1/');
            $xml->registerXPathNamespace('gndo', 'http://d-nb.info/standards/elementset/gnd#');
            // TODO: more namespaces
            $xml = $xml->xpath('rdf:Description')[0];
        } catch (Exception $e) {
            error_log($e);
        }
    }

    if (!isset($xml)) {
        return new Page();
    }

    #    print $xml->asXML();

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

    return new Page([$concept]);
}

$service = new Service(function ($q) {return GNDWrapper($q);});
$service->supportParameter('notation');
*/


