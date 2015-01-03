<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * Class PathTranslationParserFactoryTest
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PathTranslationParserFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryReturnsInstanceOfPathTranslationParserIfConfigSet()
    {
        vfsStream::setup('root');

        $package = new Package('module-package', '1.0.0', 'module-package');

        $extra = array('path-mapping-translations' => array());
        $config = new ProjectConfig($extra);

        $mockParserFactory = $this->getMock('MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface');
        $mockParserFactory
            ->expects($this->once())
            ->method('make')
            ->with($package, vfsStream::url('root'))
            ->will($this->returnValue($this->getMock('MagentoHackathon\Composer\Magento\Parser\Parser')));

        $factory = new PathTranslationParserFactory($mockParserFactory, $config);
        $instance = $factory->make($package, vfsStream::url('root'));

        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\Parser\PathTranslationParser', $instance);
    }

    public function testFactoryReturnsEmbeddedParserIfNoTranslationsFoundInConfig()
    {
        $package = new Package('module-package', '1.0.0', 'module-package');
        $config = new ProjectConfig(array());

        $parser = $this->getMock('MagentoHackathon\Composer\Magento\Parser\Parser');

        $mockParserFactory = $this->getMock('MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface');
        $mockParserFactory
            ->expects($this->once())
            ->method('make')
            ->with($package, vfsStream::url('root'))
            ->will($this->returnValue($parser));

        $factory = new PathTranslationParserFactory($mockParserFactory, $config);
        $instance = $factory->make($package, vfsStream::url('root'));

        $this->assertSame($parser, $instance);
        $this->assertNotInstanceOf('MagentoHackathon\Composer\Magento\Parser\PathTranslationParser', $instance);
    }
}
