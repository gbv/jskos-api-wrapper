<?php

/**
 * Implements a basic JSKOS concepts endpoint for Geonames.
 *
 * @package JSKOS
 */

include realpath(__DIR__) . '/lib/GeonamesService.php';

\JSKOS\Server::runService(new GeonamesService());

