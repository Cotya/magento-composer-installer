<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * Class ParserFactoryTest
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ParserFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root');
    }

    public function testMapParserIsReturnedIfMapOverwriteFound()
    {
        $package = new Package('module-package', '1.0.0', 'module-package');
        $config = new ProjectConfig(array(
            'magento-map-overwrite' => array('module-package' => array())
        ), array());
        $factory = new ParserFactory($config);
        $instance = $factory->make($package, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\MapParser', $instance);
    }

    public function testMapParserIsReturnIfModuleMapFound()
    {

        $package = new Package('module-package', '1.0.0', 'module-package');
        $package->setExtra(array('map' => array()));

        $config = new ProjectConfig(array(), array());
        $factory = new ParserFactory($config);
        $instance = $factory->make($package, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\MapParser', $instance);
    }

    public function testPackageXmlParserIsReturnedIfPackageXmlKeyIsFound()
    {

        vfsStream::newFile('Package.xml')->at($this->root);
        $package = new Package('module-package', '1.0.0', 'module-package');
        $package->setExtra(array('package-xml' => 'Package.xml'));

        $config = new ProjectConfig(array(), array());
        $factory = new ParserFactory($config);
        $instance = $factory->make($package, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\PackageXmlParser', $instance);
    }

    public function testModmanParserIsReturnedIfModmanFileIsFound()
    {

        vfsStream::newFile('modman')->at($this->root);
        $package = new Package('module-package', '1.0.0', 'module-package');

        $config = new ProjectConfig(array(), array());
        $factory = new ParserFactory($config);
        $instance = $factory->make($package, vfsStream::url('root'));
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\ModmanParser', $instance);
    }

    public function testExceptionIsThrownIfNoParserConditionsAreMet()
    {
        $this->setExpectedException(
            'ErrorException',
            'Unable to find deploy strategy for module: "module-package" no known mapping'
        );

        $package = new Package('module-package', '1.0.0', 'module-package');

        $config = new ProjectConfig(array(), array());
        $factory = new ParserFactory($config);
        $instance = $factory->make($package, vfsStream::url('root'));
    }
}
