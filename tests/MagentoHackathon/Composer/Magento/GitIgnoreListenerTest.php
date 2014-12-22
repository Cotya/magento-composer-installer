<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;

/**
 * Class GitIgnoreListenerTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class GitIgnoreListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GitIgnoreListener
     */
    protected $listener;

    /**
     * @var SymLink
     */
    protected $strategy;

    /**
     * @var PackageDeployEvent
     */
    protected $event;

    /**
     * @var GitIgnore
     */
    protected $gitIgnore;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('MagentoHackathon\Composer\Magento\GitIgnore')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new GitIgnoreListener($this->gitIgnore);
        $this->strategy = new Symlink('src', 'dest');
        $entry = new Entry();
        $entry->setPackageName('some-package');
        $entry->setDeployStrategy($this->strategy);

        $this->event = new PackageDeployEvent('post-deploy-event', $entry);
    }

    public function testInvokeUpdatesGitIgnoreEntries()
    {
        $this->strategy->addDeployedFile('file1.txt');
        $this->strategy->addDeployedFile('file2.txt');
        $this->strategy->addDeployedFile('folder1/file3.txt');

        $this->strategy->addRemovedFile('file4.txt');

        $this->gitIgnore
            ->expects($this->once())
            ->method('addMultipleEntries')
            ->with(array('file1.txt', 'file2.txt', 'folder1/file3.txt'));

        $this->gitIgnore
            ->expects($this->once())
            ->method('removeMultipleEntries')
            ->with(array('file4.txt'));

        $this->gitIgnore
            ->expects($this->once())
            ->method('write');

        $this->listener->__invoke($this->event);
    }
}
