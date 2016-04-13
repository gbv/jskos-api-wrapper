<?php

include realpath(__DIR__) . '/lib/BARTOCService.php';

\JSKOS\Server::runService(new BARTOCService());

