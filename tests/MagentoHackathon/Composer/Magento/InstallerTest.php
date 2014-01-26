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

    protected function createPackageMock(array $extra = array())
    {
        //$package= $this->getMockBuilder('Composer\Package\RootPackageInterface')
        $package = $this->getMockBuilder('Composer\Package\RootPackage')
                ->setConstructorArgs(array(md5(rand()), '1.0.0.0', '1.0.0'))
                ->getMock();
        $extraData = array_merge(array('magento-root-dir' => $this->magentoDir), $extra);

        $package->expects($this->any())
                ->method('getExtra')
                ->will($this->returnValue($extraData));

        return $package;
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getDeployStrategy
     */
    public function testGetDeployStrategyCopy()
    {
        $package = $this->createPackageMock(array('magento-deploystrategy' => 'copy'));
        $this->composer->setPackage($package);
        $installer = new Installer($this->io, $this->composer);
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Deploystrategy\Copy', $installer->getDeployStrategy($package));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getDeployStrategy
     */
    public function testGetDeployStrategySymlink()
    {
        $package = $this->createPackageMock(array('magento-deploystrategy' => 'symlink'));
        $this->composer->setPackage($package);
        $installer = new Installer($this->io, $this->composer);
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Deploystrategy\Symlink', $installer->getDeployStrategy($package));
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
        // getParser returns a modman parser by default, if map isn't set
        $package = $this->createPackageMock(array('map' => null));

        touch($this->vendorDir . DIRECTORY_SEPARATOR . 'modman');

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\ModmanParser', $this->object->getParser($package));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getParser
     */
    public function testGetMapParser()
    {
        $package = $this->createPackageMock(array('map' => array('test' => 'test')));

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\MapParser', $this->object->getParser($package));
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::appendGitIgnore
     */
    public function testGitIgnoreAppendToExistingFile()
    {
        $gitIgnoreFile      = realpath(sys_get_temp_dir()) . '/.gitignore';
        $gitIgnoreContent   = array("vendor", ".idea");
        file_put_contents($gitIgnoreFile, implode("\n", $gitIgnoreContent));

        $map = array(
            array('test1', 'test1'),
            array('testfolder1/testfile1', 'testfolder1/testfile1'),
        );
        $package = $this->createPackageMock(array('map' => $map, 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);
        $installer = new Installer($this->io, $this->composer);
        $installer->appendGitIgnore($package, $gitIgnoreFile);

        $this->assertFileExists($gitIgnoreFile);
        $expectedContent = sprintf("vendor\n.idea\n#%s\ncomposer-test-magento/test1\ncomposer-test-magento/testfolder1/testfile1", $package->getName());
        $this->assertSame(file_get_contents($gitIgnoreFile), $expectedContent);
        unlink($gitIgnoreFile);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::appendGitIgnore
     */
    public function testGitIgnoreCreateFileIfNotExist()
    {
        $gitIgnoreFile = realpath(sys_get_temp_dir()) . '/.gitignore';
        $map = array(
            array('test1', 'test1'),
            array('testfolder1/testfile1', 'testfolder1/testfile1'),
        );
        $package = $this->createPackageMock(array('map' => $map, 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);
        $installer = new Installer($this->io, $this->composer);
        $installer->appendGitIgnore($package, $gitIgnoreFile);

        $this->assertFileExists($gitIgnoreFile);
        $expectedContent = sprintf("#%s\ncomposer-test-magento/test1\ncomposer-test-magento/testfolder1/testfile1", $package->getName());
        $this->assertSame(file_get_contents($gitIgnoreFile), $expectedContent);
        unlink($gitIgnoreFile);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::install
     */
    public function testGitAppendMethodNotCalledIfOptionNotSelected()
    {
        $package = $this->createPackageMock(array('map' => array()));
        $this->composer->setPackage($package);

        $mockInstaller = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Installer')
            ->setConstructorArgs(array($this->io, $this->composer))
            ->setMethods(array('appendGitIgnore'))
            ->getMock();

        $mockInstaller->expects($this->never())
            ->method('appendGitIgnore');

        $mockInstaller->install($this->repository, $package);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::install
     */
    public function testGitAppendMethodCalledIfOptionSelected()
    {
        $gitIgnoreFile = realpath(sys_get_temp_dir()) . '/.gitignore';

        $package = $this->createPackageMock(array('map' => array(), 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);

        $mockInstaller = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Installer')
            ->setConstructorArgs(array($this->io, $this->composer))
            ->setMethods(array('getGitIgnoreFileLocation', 'appendGitIgnore'))
            ->getMock();

        $mockInstaller->expects($this->once())
            ->method('getGitIgnoreFileLocation')
            ->will($this->returnValue($gitIgnoreFile));

        $mockInstaller->expects($this->once())
            ->method('appendGitIgnore')
            ->with($package, $gitIgnoreFile);

        $mockInstaller->install($this->repository, $package);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getGitIgnoreFileLocation
     */
    public function testGetGitIgnoreFileLocationIsCorrectIfExists()
    {
        touch(".gitignore");
        $installer = new Installer($this->io, $this->composer);
        $this->assertSame($installer->getGitIgnoreFileLocation(), getcwd() . "/.gitignore");
        unlink(".gitignore");
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getGitIgnoreFileLocation
     */
    public function testGetGitIgnoreFileLocationIsCorrectIfNotExists()
    {
        $installer = new Installer($this->io, $this->composer);
        $this->assertSame($installer->getGitIgnoreFileLocation(), getcwd() . "/.gitignore");
    }

}

