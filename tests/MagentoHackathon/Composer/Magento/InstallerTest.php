<?php
namespace MagentoHackathon\Composer\Magento;

use Composer\Installer\LibraryInstaller;
use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;

class InstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Installer
     */
    protected $object;

    protected $composer;
    protected $config;
    protected $vendorDir;
    protected $binDir;
    protected $magentoDir;
    protected $dm;
    protected $repository;
    protected $io;
    /** @var Filesystem */
    protected $fs;

    protected function setUp()
    {
        $this->fs = new Filesystem;


        $this->vendorDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-vendor';
        $this->fs->ensureDirectoryExists($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-bin';
        $this->fs->ensureDirectoryExists($this->binDir);

        $this->magentoDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'composer-test-magento';
        $this->fs->ensureDirectoryExists($this->magentoDir);

        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);
        $this->composer->setPackage($this->createPackageMock());

        $this->config->merge(array(
            'config' => array(
                'vendor-dir' => $this->vendorDir,
                'bin-dir' => $this->binDir,
            ),
        ));

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->composer->setDownloadManager($this->dm);

        $this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $this->io = $this->getMock('Composer\IO\IOInterface');

        $this->object = new Installer($this->io, $this->composer);
    }

    protected function tearDown()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
        $this->fs->removeDirectory($this->magentoDir);
    }

    protected function createPackageMock()
    {
        //$package= $this->getMockBuilder('Composer\Package\RootPackageInterface')
        $package = $this->getMockBuilder('Composer\Package\RootPackage')
                ->setConstructorArgs(array(md5(rand()), '1.0.0.0', '1.0.0'))
                ->getMock();
        $package->expects($this->any())
                ->method('getExtra')
                ->will($this->returnValue(array('magento-root-dir' => $this->magentoDir)));

        return $package;
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getDeployStrategy
     */
    public function testGetDeployStrategyCopy()
    {
        $package = $this->createPackageMock();
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Deploystrategy\Copy', $this->object->getDeployStrategy($package, 'copy'));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getDeployStrategy
     */
    public function testGetDeployStrategySymlink()
    {
        $package = $this->createPackageMock();
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Deploystrategy\Symlink', $this->object->getDeployStrategy($package, 'symlink'));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::supports
     */
    public function testSupports()
    {
        $this->assertTrue($this->object->supports('magento-module'));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getParser
     */
    public function testGetModmanParser()
    {
        $package = $this->createPackageMock();
        $extra = $package->getExtra();
        // getParser returns a modman parser by default, if map isn't set
        unset($extra['map']);
        $package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue($extra));

        touch($this->vendorDir . DIRECTORY_SEPARATOR . 'modman');

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\ModmanParser', $this->object->getParser($package));
    }
}
