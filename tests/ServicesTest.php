<?php

use Symfony\Component\Yaml\Yaml;
use JSKOS\Item;
use JSKOS\Page;

class ServicesTest extends PHPUnit_Framework_TestCase {

    protected $services;

    protected function setUp() {
        $services = Yaml::parse(file_get_contents('src/services.yaml'));
        foreach ($services as $name => $about) {
            include_once "src/lib/${name}Service.php";
            $this->services[$name] = $about;
        } 
    }

    public function testCreate() {
        $serviceInstances = [];
        foreach (array_keys($this->services) as $name) {
            $class = $name."Service";
            $service = new $class();
            $this->assertInstanceOf($class, $service, "created $class");
            $serviceInstances[$name] = $service;
        } 
        return $serviceInstances;
    }

    /**
     * @depends testCreate
     */
    public function testExamples($serviceInstances) {
        foreach ($serviceInstances as $name => $service) {

            $response = $service->query([]);
            $this->assertTrue( 
                is_null($response) or $response instanceof Page, 
                "$name can be queried" 
            );

            if (isset($this->services[$name]['secret'])) {
                continue;
            }

            if (isset($this->services[$name]['examples'])) {
                foreach( $this->services[$name]['examples'] as $example ) {
                    $response = $service->query($example);
                    $this->assertTrue($response instanceof Item or $response instanceof Page );
                }
            }
        }
    }

}
