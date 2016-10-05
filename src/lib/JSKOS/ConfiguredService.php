<?php

namespace JSKOS;

use Symfony\Component\Yaml\Yaml;

class ConfiguredService extends Service {
    public static $CONFIG_DIR = "."; // subclass MUST override this, e.g. with __DIR__

    protected $config = [];
    private $uriSpaceService;

    public function __construct() {
        parent::__construct();
        $class = join('', array_slice(explode('\\', get_class($this)), -1));
        $file = static::$CONFIG_DIR."/$class.yaml";        
        $this->config = Yaml::parse(file_get_contents($file));

        if (isset($this->config['_uriSpace'])) {
            $this->uriSpaceService = new URISpaceService($this->config['_uriSpace']);
        }
    }

    public function queryURISpace($query) {
        return $this->uriSpaceService ? $this->uriSpaceService->query($query) : null;
    }
}
