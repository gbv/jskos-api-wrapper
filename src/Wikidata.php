<?php

include realpath(__DIR__) . '/lib/WikidataService.php';

\JSKOS\Server::runService(new WikidataService());

