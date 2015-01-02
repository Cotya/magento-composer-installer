<?php

namespace MagentoHackathon\Composer\Magento\Parser;

use Composer\Test\TestCase;
use Composer\Config;

/**
 * Test that path mapping translations work correctly, including different
 * prefix types (i.e. 'js/...' vs './js/...').
 */
class PathMappingTranslationTest extends \PHPUnit_Framework_TestCase
{

    public function testTranslate()
    {
        $mappings = array(
            array('src/app/etc/modules/Example_Name.xml',   'app/etc/modules/Example_Name.xml'),
            array('src/app/code/community/Example/Name',    'app/code/community/Example/Name'),
            array('src/skin',                               'skin/frontend/default/default/examplename'),
            array('src/js',                                 'js/examplename'),
            array('src/media/images',                       'media/examplename_images'),
            array('src2/skin',                              './skin/frontend/default/default/examplename'),
            array('src2/js',                                './js/examplename'),
            array('src2/media/images',                      './media/examplename_images'),
        );

        $translations = array(
            'js/'       =>  'public/js/',
            'media/'    =>  'public/media/',
            'skin/'     =>  'public/skin/',
        );

        $parser = new PathTranslationParser(new MapParser($mappings), $translations);

        $expected = array(
            array('src/app/etc/modules/Example_Name.xml',   'app/etc/modules/Example_Name.xml'),
            array('src/app/code/community/Example/Name',    'app/code/community/Example/Name'),
            array('src/skin',                               'public/skin/frontend/default/default/examplename'),
            array('src/js',                                 'public/js/examplename'),
            array('src/media/images',                       'public/media/examplename_images'),
            array('src2/skin',                              'public/skin/frontend/default/default/examplename'),
            array('src2/js',                                'public/js/examplename'),
            array('src2/media/images',                      'public/media/examplename_images'),
        );

        $this->assertEquals($expected, $parser->getMappings());
    }
}
