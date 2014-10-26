<?php
namespace MagentoHackathon\Composer\Magento;

use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;
use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;

class ModuleInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Installer
     */
    protected $object;

    /** @var  Composer */
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

        $this->object = new ModuleInstaller($this->io, $this->composer);
    }

    protected function tearDown()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
        $this->fs->removeDirectory($this->magentoDir);
    }

    protected function createPackageMock(array $extra = array(), $name = 'example/test')
    {
        //$package= $this->getMockBuilder('Composer\Package\RootPackageInterface')
        $package = $this->getMockBuilder('Composer\Package\RootPackage')
                ->setConstructorArgs(array(md5(rand()), '1.0.0.0', '1.0.0'))
                ->getMock();
        $extraData = array_merge(array('magento-root-dir' => $this->magentoDir), $extra);

        $package->expects($this->any())
                ->method('getExtra')
                ->will($this->returnValue($extraData));
        
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $package;
    }

    /**
     * @dataProvider deployMethodProvider
     */
    public function testGetDeployStrategy( $strategy, $expectedClass, $composerExtra = array(), $packageName )
    {
        $extra = array('magento-deploystrategy' => $strategy);
        $extra = array_merge($composerExtra, $extra);
        $package = $this->createPackageMock($extra,$packageName);
        $this->composer->setPackage($package);
        $installer = new ModuleInstaller($this->io, $this->composer);
        $this->assertInstanceOf($expectedClass, $installer->getDeployStrategy($package));
    }

    /**
     * 
     */
    public function testSupports()
    {
        $this->assertTrue($this->object->supports('magento-module'));
    }

    /**
     * @dataProvider parserTypeProvider
     */
    public function testGetParser( $packageExtra, $expectedClass, $composerExtra, $packageName, $prepareCallback )
    {
        $composerExtra = array_merge( $composerExtra, $this->composer->getPackage()->getExtra() );
        $this->composer->setPackage($this->createPackageMock($composerExtra));
        
        $package = $this->createPackageMock( $packageExtra, $packageName );
        $prepareCallback($this->vendorDir);
        $this->assertInstanceOf($expectedClass, $this->object->getParser($package));
    }
    
    public function deployMethodProvider()
    {
        $deployOverwrite = array(
            'example/test2' => 'symlink',
            'example/test3' => 'none',
        );
        
        return array(
            array(
                'method' => 'copy',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Copy',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'link',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Link',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'none',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'composerExtra' => array(  ),
                'packageName'   => 'example/test1',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
                'composerExtra' => array( 'magento-deploystrategy-overwrite' => $deployOverwrite ),
                'packageName'   => 'example/test2',
            ),
            array(
                'method' => 'symlink',
                'expectedClass' => 'MagentoHackathon\Composer\Magento\Deploystrategy\None',
                'composerExtra' => array( 'magento-deploystrategy-overwrite' => $deployOverwrite ),
                'packageName'   => 'example/test3',
            ),
        );
    }
    
    public function parserTypeProvider()
    {
        $mapOverwrite = array(
            'example/test2' => array('test' => 'test2'),
            'example/test3' => array('test' => 'test3'),
        );
        return array(
            array(
                'packageExtra'  => array('map' => array('test' => 'test')),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){
                        
                    },
            ),
            array(
                'packageExtra'  => array('map' => null),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\ModmanParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){
                        touch($vendorDir . DIRECTORY_SEPARATOR . 'modman');
                    },
            ),
            array(
                'packageExtra'  => array('map' => null, 'package-xml' => 'package.xml'),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\PackageXmlParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){
                        touch($vendorDir . DIRECTORY_SEPARATOR . 'package.xml');
                    },
            ),
            array(
                'packageExtra'  => array('map' => array('test' => 'test')),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){

                    },
            ),
            array(
                'packageExtra'  => array('map' => null),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\ModmanParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test1',
                'prepareCallback' => function($vendorDir){
                        touch($vendorDir . DIRECTORY_SEPARATOR . 'modman');
                    },
            ),
            array(
                'packageExtra'  => array('map' => null),
                'expectedClass' => 'MagentoHackathon\Composer\Magento\MapParser',
                'composerExtra' => array( 'magento-map-overwrite' => $mapOverwrite  ),
                'packageName'   => 'example/test2',
                'prepareCallback' => function($vendorDir){
                        touch($vendorDir . DIRECTORY_SEPARATOR . 'modman');
                    },
            ),
        );
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
     * 
     */
    public function testEtcPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/app/etc/modules/Example_Name.xml', 'app/etc/modules/Example_Name.xml'), $mappings);
    }

    /**
     * 
     */
    public function testCodePathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/app/code/community/Example/Name', 'app/code/community/Example/Name'), $mappings);
    }

    /**
     * 
     */
    public function testJSPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/js', 'js/examplename'), $mappings);
    }

    /**
     * 
     */
    public function testSkinPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/skin', 'skin/frontend/default/default/examplename'), $mappings);
    }

    /**
     * 
     */
    public function testMediaPathMappingTranslation()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src/media/images', 'media/examplename_images'), $mappings);
    }

    /**
     * 
     */
    public function testJSPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/js', './js/examplename'),$mappings);
    }

    /**
     * 
     */
    public function testSkinPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/skin', './skin/frontend/default/default/examplename'), $mappings);
    }

    /**
     * 
     */
    public function testMediaPathMappingTranslation2()
    {
        $package = $this->createPathMappingTranslationMock();
        $mappings = $this->object->getParser($package)->getMappings();

        $this->assertContains(array('src2/media/images', './media/examplename_images'), $mappings);
    }

}

