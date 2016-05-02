<?php

class JSKOSRDFMapping {

    protected $map;

    public function __construct($map) {
        $this->map = $map;
    }

    public function rdf2jskos($rdf, $jskos) {
        foreach ($this->map as $property => $mapping)
        {
            $type = $mapping['type'];

            foreach ( $mapping['properties'] as $rdfProperty ) {

                if ($type == 'URI') 
                { 
                    foreach ( $rdf->allResources($rdfProperty) as $resource ) {
                        if (!isset($jskos->$property)) {
                            $jskos->$property = [];
                        }
                        array_push( $jskos->$property, ['uri' => (string)$resource] );
                    }

                } elseif ($type == 'literal')
                {
                    foreach ( $rdf->allLiterals($rdfProperty) as $literal ) {
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
                    foreach ( $rdf->allLiterals($rdfProperty) as $literal ) {
                        $value = (string)$literal;
                        if (!isset($jskos->$property)) {
                            $jskos->$property = [];
                        }
                        array_push( $jskos->$property, $value );
                    }
                }
            }
        }
    }
}

