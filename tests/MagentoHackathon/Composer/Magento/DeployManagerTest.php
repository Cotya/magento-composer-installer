<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;

/**
 * Class DeployManagerTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class DeployManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DeployManager
     */
    protected $deployManager;

    /**
     * @var \MagentoHackathon\Composer\Magento\Event\EventManager
     */
    protected $eventManager;
    protected function setUp(): void
    {
        $this->eventManager = $this->createMock('MagentoHackathon\Composer\Magento\Event\EventManager');
        $this->deployManager = new DeployManager($this->eventManager);
    }

    public function testEventsAreFiredForEachPackageDeploy()
    {
        $this->deployManager->addPackage($package1 = $this->createPackageEntry());
        $this->deployManager->addPackage($package2 = $this->createPackageEntry());

        $package1->getDeployStrategy()
            ->expects($this->once())
            ->method('deploy');

        $package2->getDeployStrategy()
            ->expects($this->once())
            ->method('deploy');

        $this->eventManager
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->with($this->isInstanceOf('MagentoHackathon\Composer\Magento\Event\PackageDeployEvent'));

        $this->deployManager->doDeploy();
    }

    public function createPackageEntry()
    {
        $entry = new Entry;
        $strategy = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Deploystrategy\None')
            ->setConstructorArgs(array('src', 'dest'))
            ->getMock();
        $entry->setDeployStrategy($strategy);
        return $entry;
    }
}
