<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use org\bovigo\vfs\vfsStream;

/**
 * Class ModmanParserTest
 * @package MagentoHackathon\Composer\Magento\Parser
 */
class ModmanParserTest extends \PHPUnit_Framework_TestCase
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
        $parser = new ModmanParser(vfsStream::url('root/ModmanValid'));

        $expected = array(
            array('line/with/tab', 'record/one'),
            array('line/with/space', 'record/two'),
            array('line/with/space/and/tab', 'record/three')
        );
        $this->assertSame($expected, $parser->getMappings());
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\ModmanParser::getMappings
     */
    public function testGetMappingsNew()
    {
        $expected = array(
            array('line/one', 'line/one'),
            array('line/two', 'line/two'),
        );
        $this->object->setFile($this->modmanFileDir . 'modman.new_format');
        $this->assertSame($expected, $this->object->getMappings());
    }

    public function testExceptionIsThrownIfLineLessThan2Parts()
    {
        $parser = new ModmanParser(vfsStream::url('root/ModmanInvalid'));
        $this->setExpectedException('ErrorException', 'Invalid row on line 0 has 1 parts, expected 2');
        $parser->getMappings();
    }

    public function testExceptionIsThrowIfFileNotReadable()
    {
        $parser = new ModmanParser(vfsStream::url('root/ModmanValid'));
        chmod(vfsStream::url('root/ModmanValid'), 0000);
        $this->setExpectedException('ErrorException', 'modman file "vfs://root/ModmanValid" not readable');
        $parser->getMappings();
    }
}
