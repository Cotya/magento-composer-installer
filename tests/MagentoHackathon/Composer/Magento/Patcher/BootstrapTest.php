<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

/**
 * @group patcher
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider mageFileProvider
     * @param $mageFile
     */
    public function testMageFileIsChangedAfterPatching($mageFile)
    {
        $structure = array('app' => array('Mage.php' => file_get_contents($mageFile)));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertTrue($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileNotEquals($mageFile, $mageClassFile);
    }

    /**
     * @dataProvider mageFileProvider
     */
    public function testMageFileIsNotModifiedWhenThePatchingFeatureIsOff($mageFile)
    {
        $structure = array('app' => array('Mage.php' => file_get_contents($mageFile)));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => false,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFileEquals($mageFile, $mageClassFile);
    }

    /**
     * @dataProvider mageFileProvider
     */
    public function testBootstrapPatchIsAppliedByDefault($mageFile)
    {
        $structure = array('app' => array('Mage.php' => file_get_contents($mageFile)));
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            // the patch flag is not declared on purpose
            array(ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root')),
            array()
        );

        $mageClassFile = vfsStream::url('root/app/Mage.php');
        $patcher = Bootstrap::fromConfig($config);

        $this->assertTrue($patcher->canApplyPatch());
        $this->assertFileEquals($mageFile, $mageClassFile);

        $patcher->patch();

        $this->assertFalse($patcher->canApplyPatch());
        $this->assertFileNotEquals($mageFile, $mageClassFile);
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
