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

    /*
     * Test that path mapping translation code doesn't have any effect when no
     * translations are specified.
     */

    protected function createPathMappingTranslationMock()
    {
        return $this->createPackageMock(
            array(
                'map' => array(
                    array('src/app/etc/modules/Example_Name.xml',   'app/etc/modules/Example_Name.xml'),
                    array('src/app/code/community/Example/Name',    'app/code/community/Example/Name'),
                    array('src/skin',                               'skin/frontend/default/default/examplename'),
                    array('src/js',                                 'js/examplename'),
                    array('src/media/images',                       'media/examplename_images'),
                    array('src2/skin',                              './skin/frontend/default/default/examplename'),
                    array('src2/js',                                './js/examplename'),
                    array('src2/media/images',                      './media/examplename_images'),
                )
            )
        );
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testEtcPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/app/etc/modules/Example_Name.xml', 'app/etc/modules/Example_Name.xml'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testCodePathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/app/code/community/Example/Name', 'app/code/community/Example/Name'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testJSPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/js', 'js/examplename'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/skin', 'skin/frontend/default/default/examplename'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/media/images', 'media/examplename_images'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testJSPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/js', './js/examplename'),$mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testSkinPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/skin', './skin/frontend/default/default/examplename'), $mappings);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer::getMappings
     */
    public function testMediaPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/media/images', './media/examplename_images'), $mappings);
    }
}
