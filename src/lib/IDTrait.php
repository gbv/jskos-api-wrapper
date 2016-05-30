<?php

trait IDTrait
{
    /**
     * Get id from query parameter uri and/or notation.
     */
    public function idFromQuery($query, $uriRegex, $notationRegex=null) 
    {
        if ($uriRegex and isset($query['uri'])) {
            if (preg_match($uriRegex, $query['uri'], $match)) {
                $id = $match[1];
            }
        }
            
        if ($notationRegex and isset($query['notation'])) {
            if (preg_match($notationRegex, $query['notation'])) {
                $notation = $query['notation'];
                if (isset($id) and $id != $notation) {
                    unset($id);
                } else {
                    $id = $notation;
                }
            }
        }

        return isset($id) ? $id : null;
    }

}

