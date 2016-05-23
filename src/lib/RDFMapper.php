<?php

use Symfony\Component\Yaml\Yaml;

/**
 * Maps RDF to JSKOS based on a YAML mapping file.
 */
class RDFMapper 
{
    private $rdfmapping;

    public static $JSKOSClasses = [
        'Concept', 'ConceptScheme', 'ConceptType', 'ConceptBundle', 'Concordance', 'Mapping'
    ];

    /**
     * Silently load RDF from an URL.
     * @return EasyRDF_Resource|null
     */
    public static function loadRDF($url, $uri=NULL, $format=NULL)
    {
        try { 
            $rdf = EasyRdf_Graph::newAndLoad($url, $format); 
            return $rdf->resource( $uri ? $uri : $url );
        } catch( Exception $e ) {
            return;
        }
    }

    /**
     * Load mapping from YAML file and store it in a static class variable.
     */
    public function __construct($file) 
    {
        $map = Yaml::parse(file_get_contents($file));
        if (!$map) {
            throw new Exception("Failed to load YAML from $file");
        } else {
            # error_log("Got mapping from $file");
        }

        if ($map['_ns']) {
            foreach ($map['_ns'] as $prefix => $namespace) {
                # TODO: warn if prefix is already defined with different namespace!
                EasyRdf_Namespace::set($prefix, $namespace);
            }
            unset($map['_ns']);
        }

        $this->rdfmapping = $map;
    }

    /**
     * Get a list of RDF object URIs.
     */
    public static function getURIs( EasyRDF_Resource $subject, $predicate, $pattern = null ) 
    {
        $uris = [];
        foreach ( $subject->allResources($predicate) as $object ) {
            if ( !$object->isBNode() ) {
                $object = $object->getUri();
                if ( !$pattern or preg_match($pattern, $object) ) {
                    $uris[] = $object;
                }
            }
        }
        return $uris;
    }

    /**
     * Apply mapping via extraction of data from an RDF resource and add 
     * resulting data to a JSKOS Object.
     *
     * @param EasyRdf_Resource rdf
     * @param JSKOS\Object jskos
     */
    public function rdf2jskos(EasyRdf_Resource $rdf, \JSKOS\Object $jskos, $defaultLang = 'en') 
    {
        # error_log($rdf->getGraph()->dump('text'));
        foreach ($this->rdfmapping as $property => $mapping)
        {
            $type   = $mapping['type'];
            if ( isset($mapping['jskos']) && in_array($mapping['jskos'], static::$JSKOSClasses) ) {
                $class = '\JSKOS\\'.$mapping['jskos'];
            } else {
                $class = null;
            }

            foreach ( $mapping['properties'] as $rdfProperty ) 
            {
                if ($type == 'URI')
                { 
                    foreach ( static::getURIs($rdf, $rdfProperty) as $uri ) {
                        if (isset($class)) {
                            $uri = new $class(['uri'=>$uri]);
                        }
                        if (isset($mapping['unique'])) {
                            $jskos->$property = $uri;
                        } else {
                            if (!isset($jskos->$property)) {
                                $jskos->$property = [];
                            }
                            array_push( $jskos->$property, $uri );
                        }
                    }

                } elseif ($type == 'literal')
                {
                    foreach ( $rdf->allLiterals($rdfProperty) as $literal ) {
                        $value = (string)$literal;
                        $language = $literal->getLang() ? $literal->getLang() : $defaultLang;

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
                        if (isset($mapping['pattern']) && !preg_match($mapping['pattern'], $value)) {
                            continue;
                        }
                        if (isset($mapping['unique'])) {
                            $jskos->$property = $value;
                        } else {
                            if (!isset($jskos->$property)) {
                                $jskos->$property = [$value];
                            } else {
                                array_push( $jskos->$property, $value );
                            }
                        }
                    }
                }
            }
        }
    }
}
