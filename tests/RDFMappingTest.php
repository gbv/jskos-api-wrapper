<?php

use JSKOS\Concept;
use JSKOS\RDFMapping;

class RDFMappingTest extends PHPUnit_Framework_TestCase {

    public function testMapper() {
        $mapper = new RDFMapping(__DIR__.'/sampleMapping.yaml');

        $rdf = new EasyRdf_Graph();
        $rdf->parseFile(__DIR__.'/sampleRDF.ttl', 'turtle');

        $jskos = new Concept();
        $mapper->apply($rdf->resource('http://example.org/c0'), $jskos);

        # FIXME: result may differ because RDF has no intrinsic order (?)
        $expect = new Concept([
            "prefLabel" => ["en" => "foo", "de" => "bar"],
            "altLabel" => ["en" => ["CONCEPT"], "de" => ["BEGRIFF","KONZEPT"] ],
            "broader"  => [
                ["uri" => "http://example.org/c0"],
                ["uri" => "http://example.org/c1"],
                ["uri" => "http://example.org/c2"],
            ],
            "relatedDate" => ["2009-01-01", "2010-01-01"],
        ]);

        $this->assertEquals($expect->json(), $jskos->json());
    }
}

