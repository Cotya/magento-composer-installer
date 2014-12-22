<?php

use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;

/**
 * Class PackageDeployEventTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageDeployEventTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $eventName      = 'pre-package-deploy';
        $deployEntry    = new Entry();
        $event          = new PackageDeployEvent($eventName, $deployEntry);

        $this->assertEquals($eventName, $event->getName());
        $this->assertSame($deployEntry, $event->getDeployEntry());
    }
}
