<?php

use Symfony\Component\Yaml\Yaml;

/**
 * Mapping of RDF data to JSKOS object.
 *
 * The mapping is controlled by a mapping of JSKOS field names
 * to RDF properties with some additional configuration. 
 */
class JSKOSRDFMapping {

    /**
     * The actual mapping
     * @var array
     */
    public $map;

    /**
     * Create a mapping from array or YAML file.
     */ 
    public function __construct($map) {
        if (is_string($map)) {
            $yaml = Yaml::parse(file_get_contents($map));
            if (!$yaml) {
                throw new Exception("Failed to load YAML from $map");
            }
            $map = $yaml;
        }

        if ($map['_ns']) {
            foreach ($map['_ns'] as $prefix => $namespace) {
                # TODO: warn if prefix already defined!
                EasyRdf_Namespace::set($prefix, $namespace);
            }
            unset($map['_ns']);
        }

        $this->map = $map;
    }

    /**
     * Apply mapping via extraction of data from an RDF resource and add 
     * resulting data to a JSKOS Object.
     *
     * @param EasyRdf_Resource rdf
     * @param JSKOS\Object jskos
     */
    public function rdf2jskos(EasyRdf_Resource $rdf, \JSKOS\Object $jskos) {
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
                } elseif ($type == 'plain') {
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

