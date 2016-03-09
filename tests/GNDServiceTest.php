<?php

include 'wrappers/lib/GNDService.php';

/**
 * @covers GNDService
 */
class GNDServiceTest extends PHPUnit_Framework_TestCase {

    public function testExample() {
        $service = new GNDService();
        $response = $service->query(['notation'=>'118540475']);
        $this->assertInstanceOf('JSKOS\Concept', $response);
    }

}
