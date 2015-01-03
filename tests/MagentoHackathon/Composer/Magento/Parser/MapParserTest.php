<?php

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Class MapParserTest
 * @package MagentoHackathon\Composer\Magento\Parser
 */
class MapParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers MagentoHackathon\Composer\Magento\Parser\MapParser::getMappings
     */
    public function testGetMappings()
    {
        $expected = array(
            array('line/with/tab', 'record/one'),
            array('line/with/space', 'record/two'),
            array('line/with/space/and/tab', 'record/three')
        );

        $parser = new MapParser($expected);

        $this->assertSame($expected, $parser->getMappings());
    }
}
