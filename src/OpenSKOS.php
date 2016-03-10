<?php

include realpath(__DIR__) . '/lib/OpenSKOSService.php';

\JSKOS\Server::runService(new OpenSKOSService());

