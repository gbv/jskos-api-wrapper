<?php

$service = 'GeonamesService';

include realpath(__DIR__) . "/lib/$service.php";
\JSKOS\Server::runService(new $service());
