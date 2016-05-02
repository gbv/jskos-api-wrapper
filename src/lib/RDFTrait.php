<?php

trait RDFTrait 
{
    /**
     * Silently load RDF from an URL.
     * @return EasyRDF_Resource|null
     */
    function loadRDF($uri) 
    {
        try { 
            $rdf = EasyRdf_Graph::newAndLoad($uri); 
            return $rdf->resource($uri);
        } catch( Exception $e ) {
            return;
        }
    }
}
