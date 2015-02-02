<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use org\bovigo\vfs\vfsStream;


/**
 * 
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{

    /**
     * 
     * @dataProvider mageFileProvider
     * @param $MageFile
     */
    public function testMageFiles($MageFile)
    {

        $structure = array(
            'app' => array(
                'Mage.php' => file_get_contents($MageFile),
            ),
        );
        $directory = vfsStream::setup('patcherMagentoBase', null, $structure);
        $patcher = new Bootstrap(vfsStream::url('patcherMagentoBase'));
        $patcher->patch();
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.php'));
        $this->assertFileNotEquals(
            $MageFile,
            vfsStream::url('patcherMagentoBase/app/Mage.php'),
            'File should be modified but its not'
        );
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/bootstrap.php'));
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.class.php'));
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.bootstrap.php'));
        $this->assertFileNotExists(vfsStream::url('patcherMagentoBase/app/Mage.nonsense.php'));
    }
    
    public function mageFileProvider()
    {
        $fixturesBasePath = __DIR__ . '/../../../../res/fixtures';
        $data = array(
            array($fixturesBasePath . '/php/Mage/Mage-v1.9.1.0.php')
        );
        return $data;
    }
}
