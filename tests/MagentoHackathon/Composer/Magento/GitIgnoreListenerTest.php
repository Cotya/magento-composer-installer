<?php

namespace MagentoHackathon\Composer\Magento;

use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Symlink;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;

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
     * @var GitIgnore
     */
    protected $gitIgnore;

    public function setUp()
    {
        $this->gitIgnore = $this->getMockBuilder('MagentoHackathon\Composer\Magento\GitIgnore')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new GitIgnoreListener($this->gitIgnore);
    }

    public function testAddNewInstalledFilesUpdatesGitIgnoreEntries()
    {
        $strategy = new Symlink('src', 'dest');
        $strategy->addDeployedFile('file1.txt');
        $strategy->addDeployedFile('file2.txt');
        $strategy->addDeployedFile('folder1/file3.txt');

        $entry = new Entry();
        $entry->setPackageName('some-package');
        $entry->setDeployStrategy($strategy);

        $e = new PackageDeployEvent('post-deploy-event', $entry);

        $this->gitIgnore
            ->expects($this->once())
            ->method('addMultipleEntries')
            ->with(['file1.txt', 'file2.txt', 'folder1/file3.txt']);

        $this->gitIgnore
            ->expects($this->once())
            ->method('write');

        $this->listener->addNewInstalledFiles($e);
    }

    public function testRemoveInstalledFilesUpdatesGitIgnoreEntries()
    {
        $package = new InstalledPackage('1.0.0', '1.0.0', ['file1.txt', 'file2.txt']);
        $e = new PackageUnInstallEvent('post-package-uninstall', $package);

        $this->gitIgnore
            ->expects($this->once())
            ->method('removeMultipleEntries')
            ->with(['file1.txt', 'file2.txt']);

        $this->listener->removeUnInstalledFiles($e);
    }
}
