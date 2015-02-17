<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\Package;
use MagentoHackathon\Composer\Magento\Deploystrategy\None;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * Class EntryFactoryTest
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class EntryFactoryTest extends \PHPUnit_Framework_TestCase
{

    protected $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root', null, array('vendor' => array(), 'htdocs' => array()));
    }

    public function testFactoryCreatesEntry()
    {
        $packageSourceDir = sprintf('%s/some/package', vfsStream::url('root/vendor'));
        $package = new Package("some/package", "1.0.0", "some/package");

        $deployStrategyFactory = $this
            ->getMockBuilder('MagentoHackathon\Composer\Magento\Factory\DeploystrategyFactory')
            ->disableOriginalConstructor()
            ->setMethods(array('make'))
            ->getMock();

        $deployStrategy = new None('src', 'dest');

        $deployStrategyFactory
            ->expects($this->once())
            ->method('make')
            ->with($package, $packageSourceDir)
            ->will($this->returnValue($deployStrategy));

        $parser = $this->getMock('MagentoHackathon\Composer\Magento\Parser\Parser');
        $parser->expects($this->once())
            ->method('getMappings')
            ->will($this->returnValue(array()));

        $parserFactory = $this->getMock('MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface');
        $parserFactory
            ->expects($this->once())
            ->method('make')
            ->with($package, $packageSourceDir)
            ->will($this->returnValue($parser));

        $config = new ProjectConfig(array(), array());
        $factory = new EntryFactory($config, $deployStrategyFactory, $parserFactory);

        $instance = $factory->make($package, $packageSourceDir);
        $this->assertInstanceOf('\MagentoHackathon\Composer\Magento\Deploy\Manager\Entry', $instance);
    }
}
