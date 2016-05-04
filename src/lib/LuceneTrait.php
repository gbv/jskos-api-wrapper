<?php

trait LuceneTrait
{
    /**
     * Escape special characters used in Lucene Query Parser Syntax.
     */
    public function luceneQuery($field, $query) {
        $query = preg_replace(
            '/([*+&|!(){}\[\]^"~*?:\\-])/',
            '\\\\$1',
            $query 
        );
        return "$field:\"$query\"";
    }
}
