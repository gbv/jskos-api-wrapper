<?php

use Symfony\Component\Yaml\Yaml;

# use EayRDF_Namespace;
# use EasyRDF_Graph;
# use JSKOS\Object;

trait RDFTrait 
{
    public static $rdfmapping;

    /**
     * Silently load RDF from an URL.
     * @return EasyRDF_Resource|null
     */
    function loadRDF($url, $uri=NULL, $format=NULL)
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
    function loadMapping($file) {
        if (static::$rdfmapping) {
            return;
        }

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

        static::$rdfmapping = $map;
    }

    /**
     * Apply mapping via extraction of data from an RDF resource and add 
     * resulting data to a JSKOS Object.
     *
     * @param EasyRdf_Resource rdf
     * @param JSKOS\Object jskos
     */
    public function rdf2jskos(EasyRdf_Resource $rdf, \JSKOS\Object $jskos, $defaultLang = 'en') {
        foreach (static::$rdfmapping as $property => $mapping)
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
