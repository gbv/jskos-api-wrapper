<?php

/**
 * Provides the list of Services via ELMA API.
 */

include realpath(__DIR__.'/../..') . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use JSKOS\Service;
use JSKOS\ConceptScheme;

class ServiceService extends Service {

    protected $services;

    public function query($query) {

        if (!isset($query['uri'])) {
            return;
        }

        if (!$this->services) {
            $this->services = Yaml::parse(file_get_contents(__DIR__.'/../services.yaml'));
            error_log($this->services);
        }

        foreach ($this->services as $service) {
            if ($service['uri'] == $query['uri']) {
                unset($service['SECRET']);
                return new ConceptScheme($service);
            }
        }

        return;
    }
}
