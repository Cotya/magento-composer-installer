<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use org\bovigo\vfs\vfsStream;

/**
 * Class PackageXmlParserTest
 * @package MagentoHackathon\Composer\Magento\Parser
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageXmlParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $fixtureDir;

    public function setUp()
    {
        $root = vfsStream::setup('root');
        vfsStream::copyFromFileSystem(realpath(__DIR__ . '/../../../../res/fixtures'), $root);
    }

    public function testGetMappings()
    {
        $parser = new PackageXmlParser(vfsStream::url('root/PackageXmlValid.xml'));

        $expected = array (
            array('./app/code/community/Some/Module/Block/Block.php', './app/code/community/Some/Module/Block/Block.php'),
            array('./app/code/community/Some/Module/Helper/Data.php', './app/code/community/Some/Module/Helper/Data.php'),
            array('./app/code/community/Some/Module/Model/Model.php', './app/code/community/Some/Module/Model/Model.php'),
            array('./app/code/community/Some/Module/etc/config.xml', './app/code/community/Some/Module/etc/config.xml'),
            array('./app/design/adminhtml/default/default/layout/layout.xml', './app/design/adminhtml/default/default/layout/layout.xml'),
            array('./app/design/adminhtml/default/default/template/module/template.phtml', './app/design/adminhtml/default/default/template/module/template.phtml'),
            array('./app/etc/modules/Some_Module.xml', './app/etc/modules/Some_Module.xml'),
            array('./skin/frontend/base/default/images/somemodule/image.png', './skin/frontend/base/default/images/somemodule/image.png'),
        );

        $this->assertSame($expected, $parser->getMappings());
    }

    public function testExceptionIsThrowIfFileNotReadable()
    {
        $parser = new PackageXmlParser(vfsStream::url('root/PackageXmlValid.xml'));
        chmod(vfsStream::url('root/PackageXmlValid.xml'), 0000);
        $this->setExpectedException('ErrorException', 'Package file "vfs://root/PackageXmlValid.xml" not readable');
        $parser->getMappings();
    }

    public function testInvalidTargetTypeContinuesToProcess()
    {
        $parser = new PackageXmlParser(vfsStream::url('root/PackageXmlInvalidTarget.xml'));
        $expected = array (
            array('./app/code/community/Some/Module/Block/Block.php', './app/code/community/Some/Module/Block/Block.php'),
        );

        $this->assertSame($expected, $parser->getMappings());
    }

    public function testInvalidPathTypeContinuesToProcess()
    {
        $parser = new PackageXmlParser(vfsStream::url('root/PackageXmlInvalidPath.xml'));
        $expected = array (
            array('./app/code/community/Some/Module/Block/Block.php', './app/code/community/Some/Module/Block/Block.php'),
        );

        $this->assertSame($expected, $parser->getMappings());
    }
}
