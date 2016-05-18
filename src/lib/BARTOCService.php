<?php

/**
 * Basic JSKOS concept schemes endpoint for BARTOC.org.
 */

include_once __DIR__.'/../../vendor/autoload.php';

use JSKOS\Service;
use JSKOS\Concept;
use JSKOS\ConceptScheme;
use JSKOS\Page;
use JSKOS\Error;

use Symfony\Component\Yaml\Yaml;

// TODO: move to another place
function loadCSV( $file, $key=null ) {
    $rows = array_map('str_getcsv', file($file));
    $keys = array_shift($rows);
    foreach( $rows as $row ) {
        $row = array_combine($keys, $row);
        $rows[$row[$key]] = $row;
    }
    return $rows;
}

class BARTOCService extends Service {
    use IDTrait;
    
    protected $supportedParameters = ['notation','search'];

    private $rdfMapper;
    private $languages = [];
    private $licenses  = [];
    
    public function __construct() {
        $this->rdfMapper = new RDFMapper(__DIR__.'/BARTOCMapping.yaml');
        $this->languages = loadCSV( __DIR__.'/BARTOC/languages.csv', 'bartoc' );
        $this->licenses = loadCSV( __DIR__.'/BARTOC/licenses.csv', 'bartoc' );
        parent::__construct();
    }

    public function query($query) {
        $id = $this->idFromQuery($query, '/^http:\/\/bartoc\.org\/en\/node\/([0-9]+)$/', '/^[0-9]+$/');
        if (isset($id)) {
            return $this->lookupByURI("http://bartoc.org/en/node/$id");
        } elseif (isset($query['search'])) {
            return new Page( $this->search($query['search']) );
        } else {
            return;
        }
    }

    public function lookupByURI($uri) {
        $rdf = RDFMapper::loadRDF($uri);
        if (!$rdf) return;

        // FIXME: There is a bug in Drupal RDFa output. This is a dirty hack to repair.
        foreach ( ['dct:subject', 'dct:type', 'dct:language', 'dct:format', 'schema:license'] 
            as $predicate) {
            foreach( $rdf->allResources($predicate) as $bnode ) {
                if ($bnode->isBNode()) {
                    foreach ($bnode->properties() as $p) {
                        foreach ($bnode->allResources($p) as $o) {
                            $rdf->add($predicate,$o);
                        }
                    }   
                    # FIXME: bug in EasyRDF?!
                    # $rdf->getGraph()->deleteResource($rdf->getUri(), $predicate, $bnode);
                }
            }
        }

        $jskos = new ConceptScheme(['uri' => $uri]);

        $this->rdfMapper->rdf2jskos($rdf, $jskos, 'en'); 
        # error_log($rdf->getGraph()->dump('text'));

        # map licenses
        foreach ( RDFMapper::getURIs($rdf, 'schema:license') as $license ) {
            if (isset($this->licenses[$license])) {
                $jskos->license[] = ['uri'=>$this->licenses[$license]['uri']];
            } else {
                $jskos->license[] = ['prefLabel'=>['en'=>'Unknown license']];
                error_log("Unknown license: $license");
            }
        }

        # ISO 639-2 (primary) or ISO 639-2/T (Terminology, three letter)
        foreach ( RDFMapper::getURIs($rdf, 'dc:language') as $language ) {
            if (isset($this->languages[$language])) {
                $jskos->languages[] = $this->languages[$language]['iana'];
            } else {
                error_log("Unknown language: $language");
            }
        }
        
        $names = [];
        foreach ($rdf->allLiterals('schema:name') as $name) {
            $value = $name->getValue();
            if (preg_match('/^[A-Z]{2,5}$/', $value)) {
                $jskos->notation = [ $value ];
            } elseif( $name->getDatatypeUri() == "http://id.loc.gov/vocabulary/iso639-2/eng" ) {
                $jskos->prefLabel['en'] = $value;
            } else {
                $names[] = $value;
            }
        }

        $defaultLanguage = count($jskos->languages) == 1 ? $jskos->languages[0] : 'und';

        $prefLabels = [];
        foreach ($rdf->allLiterals('skos:prefLabel') as $name) {
           $prefLabels[] = $name->getValue();
        }
        if (count($prefLabels) == 1) {
            $jskos->prefLabel[$defaultLanguage] = $prefLabel[0];
        } else {
            $names = array_unique(array_merge($names,$prefLabels));
        }

        if (count($jskos->languages) == 1 && count($names) == 1) {
            $jskos->prefLabel[ $jskos->languages[0] ] = $names[0];
        } else {
            error_log("Languages: ". implode(", ",$jskos->languages));
            if (count($names) == 1) {
                $jskos->prefLabel['und'] = $names[0];
            } else {
                $jskos->altLabel['und'] = $names;
            }
        }

        return $jskos;
    }

    /**
     * Basic search as proof of concept. Of little use without a Drupal search API.
     */
    private function search($search) {
        $query = 'en/autocomplete_filter/title/title_finder/page/0/'.$search;
        $url = 'http://bartoc.org/index.php?' . http_build_query(['q'=>$query]);
        try {
            $json = @json_decode( @file_get_contents($url) );
            $schemas = [];
            foreach ( $json as $key => $value ) {
                # unfortunately IDs are not included in the result!
                $schemas[] = new ConceptScheme(['prefLabel' => ['en' => $key]]);
            }
            return $schemas;
        } catch (Exception $e) {
            error_log($e);
            return [];
        }
    } 
}

 
