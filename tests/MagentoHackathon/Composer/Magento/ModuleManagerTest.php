<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Factory\ParserFactory;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageFileSystemRepository;
use org\bovigo\vfs\vfsStream;

/**
 * Class ModuleManagerTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $moduleManager;
    protected $installedPackageRepository;
    protected $unInstallStrategy;
    protected $installStrategyFactory;

    public function setUp()
    {
        vfsStream::setup('root');
        $this->installedPackageRepository = new InstalledPackageFileSystemRepository(
            vfsStream::url('root/installed.json'),
            new InstalledPackageDumper()
        );

        $config = new ProjectConfig(array(), array('config' => array('vendor-dir' => 'vendor')));
        $this->unInstallStrategy =
            $this->getMock('MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategyInterface');

        $parserFactory = $this->getMock('MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface');
        $parserFactory
            ->expects($this->any())
            ->method('make')
            ->will($this->returnValue(new None('src', 'dest')));

        $this->installStrategyFactory = new InstallStrategyFactory($config, $parserFactory);
        $this->moduleManager = new ModuleManager(
            $this->installedPackageRepository,
            new EventManager,
            $config,
            $this->unInstallStrategy,
            $this->installStrategyFactory
        );
    }

    public function testPackagesRemovedFromComposerAreMarkedForUninstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.0.0", "vendor/package1")
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
            new InstalledPackage("vendor/package2", "1.0.0", array()),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);
        $this->installedPackageRepository->add($installedMagentoPackages[1]);

        $result = $this->moduleManager->updateInstalledPackages($composerInstalledPackages);

        $this->assertEmpty($result[1]);
        $this->assertSame(array($installedMagentoPackages[1]), array_values($result[0]));
    }

    public function testPackagesNotInstalledAreMarkedForInstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.0.0", "vendor/package1")
        );

        $result = $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
        $this->assertEmpty($result[0]);
        $this->assertSame(array($composerInstalledPackages[0]), $result[1]);
    }

    public function testUpdatedPackageIsMarkedForUninstallAndReInstall()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.1.0", "vendor/package1")
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);

        $result = $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
        $this->assertSame(array($installedMagentoPackages[0]), $result[0]);
        $this->assertSame(array($composerInstalledPackages[0]), $result[1]);
    }

    public function testMultipleInstallsAndUnInstalls()
    {
        $composerInstalledPackages = array(
            new Package("vendor/package1", "1.1.0", "vendor/package1"),
            new Package("vendor/package2", "1.1.0", "vendor/package2"),
        );

        $installedMagentoPackages = array(
            new InstalledPackage("vendor/package1", "1.0.0", array()),
            new InstalledPackage("vendor/package2", "1.0.0", array()),
        );

        $this->installedPackageRepository->add($installedMagentoPackages[0]);
        $this->installedPackageRepository->add($installedMagentoPackages[1]);

        $result = $this->moduleManager->updateInstalledPackages($composerInstalledPackages);
        $this->assertSame($installedMagentoPackages, $result[0]);
        $this->assertSame($composerInstalledPackages, $result[1]);
    }
}
