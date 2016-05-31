<?php

namespace JSKOS;

use Symfony\Component\Yaml\Yaml;

/**
 * RDFMapping maps RDF data to JSKOS based on a set of mapping rules.
 * Mapping rules are provided in form of a YAML file.
 *
 * The rules define which RDF properties will be used to fill which JSKOS 
 * fields (and the special keys _ns and _language).
 *
 * RDF data must be provided as EasyRdf_Resource (ARC2 may be added later).
 *
 * @license LGPL
 * @author Jakob Voß
 */
class RDFMapping
{
    /**
     * The mapping rules.
     */
    protected $rules;

    /**
     * Default language for literals without language tag.
     */
    protected $language = 'und';

    /**
     * Expected URI space.
     */
    protected $uriSpace;

    /**
     * Allowed JSKOS class names
     */
    public static $JSKOSClasses = [
        'Concept',
        'ConceptScheme',
        'ConceptType',
        'ConceptBundle',
        'Concordance',
        'Mapping'
    ];

    /**
     * Load mapping rules from YAML file and store it in a static class variable.
     */
    public function __construct($rules)
    {
        if (is_string($rules)) {
            $file = $rules;
            $rules = Yaml::parse(file_get_contents($file));
            if (!$rules) {
                throw new Exception("Failed to load YAML from $file");
            }
        }

        if ($rules['_ns']) {
            foreach ($rules['_ns'] as $prefix => $namespace) {
                # TODO: warn if prefix is already defined with different namespace!
                \EasyRdf_Namespace::set($prefix, $namespace);
            }
            unset($rules['_ns']);
        }

        if (isset($rules['_language'])) {
            $this->language = $rules['_language'];
            unset($rules['_language']);
        }

        if (isset($rules['_uriSpace'])) {
            $this->uriSpace = $rules['_uriSpace'];
            unset($rules['_uriSpace']);
        }

        $this->rules = $rules;
    }

    /**
     * Apply mapping via extraction of data from an RDF resource and add 
     * resulting data to a JSKOS Object.
     *
     * @param EasyRdf_Resource rdf
     * @param JSKOS\Object jskos
     */
    public function apply(\EasyRdf_Resource $rdf, \JSKOS\Object $jskos) 
    {
        # error_log($rdf->getGraph()->dump('text'));
        foreach ($this->rules as $property => $mapping) {
            $type   = $mapping['type'];
            if (isset($mapping['jskos']) && in_array($mapping['jskos'], static::$JSKOSClasses)) {
                $class = '\JSKOS\\'.$mapping['jskos'];
            } else {
                $class = null;
            }

            foreach ($mapping['properties'] as $rdfProperty) {
                if ($type == 'URI') {
                    foreach (static::getURIs($rdf, $rdfProperty) as $uri) {
                        if (isset($class)) {
                            $uri = new $class(['uri'=>$uri]);
                        }
                        if (isset($mapping['unique'])) {
                            $jskos->$property = $uri;
                        } else {
                            if (!isset($jskos->$property)) {
                                $jskos->$property = [];
                            }
                            array_push($jskos->$property, $uri);
                        }
                    }
                } elseif ($type == 'literal') {
                    foreach ($rdf->allLiterals($rdfProperty) as $literal) {
                        $value = (string)$literal;
                        $language = $literal->getLang() ? $literal->getLang() : $this->language;

                        $languageMap = isset($jskos->$property) ? $jskos->$property : [];

                        if (isset($mapping['unique'])) {
                            $languageMap[$language] = $value;
                        } else {
                            $languageMap[$language][] = $value;
                        }

                        $jskos->$property = $languageMap;
                    }
                } elseif ($type == 'plain') {
                    foreach ($rdf->allLiterals($rdfProperty) as $literal) {
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
                                array_push($jskos->$property, $value);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Build a local URI from uriSpace and notation.
     */
    public function buildUri($notation) 
    {
        if ($this->uriSpace) {
            return $this->uriSpace . $notation;
        }
    }

    /**
     * Silently load RDF from an URL.
     * @return EasyRdf_Resource|null
     */
    public static function loadRDF($url, $uri=null, $format=null)
    {
        try {
            $rdf = \EasyRdf_Graph::newAndLoad($url, $format);
            return $rdf->resource($uri ? $uri : $url);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Get a list of RDF object URIs.
     */
    public static function getURIs(\EasyRDF_Resource $subject, $predicate, $pattern = null)
    {
        $uris = [];
        foreach ($subject->allResources($predicate) as $object) {
            if (!$object->isBNode()) {
                $object = $object->getUri();
                if (!$pattern or preg_match($pattern, $object)) {
                    $uris[] = $object;
                }
            }
        }
        return $uris;
    }
}
