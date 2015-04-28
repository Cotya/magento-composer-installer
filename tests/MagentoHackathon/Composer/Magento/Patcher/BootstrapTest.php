<?php

namespace MagentoHackathon\Composer\Magento\Patcher;

use MagentoHackathon\Composer\Magento\ProjectConfig;
use org\bovigo\vfs\vfsStream;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider mageFileProvider
     * @param $mageFile
     */
    public function testMageFilesExist($mageFile)
    {
        $structure = array(
            'app' => array(
                'Mage.php' => file_get_contents($mageFile),
            ),
        );
        vfsStream::setup('patcherMagentoBase', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('patcherMagentoBase'),
            ),
            array()
        );

        $patcher = new Bootstrap($config);
        $patcher->patch();

        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.php'));
        $this->assertFileNotEquals(
            $mageFile,
            vfsStream::url('patcherMagentoBase/app/Mage.php'),
            'File should be modified but its not'
        );
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/bootstrap.php'));
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.class.php'));
        $this->assertFileExists(vfsStream::url('patcherMagentoBase/app/Mage.bootstrap.php'));
        $this->assertFileNotExists(vfsStream::url('patcherMagentoBase/app/Mage.nonsense.php'));
    }

    /**
     * Ensure that the Mage class is valid PHP
     *
     * @dataProvider mageFileProvider
     * @param string $mageFile
     * @return void
     */
    public function testMageClassFile($mageFile)
    {
        $structure = array(
            'app' => array(
                'Mage.php' => file_get_contents($mageFile),
            ),
        );
        vfsStream::setup('patcherMagentoBase', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => true,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('patcherMagentoBase'),
            ),
            array()
        );

        $patcher = new Bootstrap($config);
        $patcher->patch();

        require vfsStream::url('patcherMagentoBase/app/Mage.class.php');
        $this->assertTrue(class_exists('Mage'));
    }

    /**
     * @dataProvider mageFileProvider
     */
    public function testMageFileIsNotModifiedWhenThePatchingFeatureIsOff($mageFile)
    {
        $structure = array(
            'app' => array(
                'Mage.php' => file_get_contents($mageFile),
            ),
        );
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            array(
                ProjectConfig::EXTRA_WITH_BOOTSTRAP_PATCH_KEY => false,
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $patcher = new Bootstrap($config);
        $patcher->patch();

        $this->assertBootstrapWasNotApplied($mageFile);
    }

    /**
     * @dataProvider mageFileProvider
     */
    public function testBootstrapPatchIsNotAppliedByDefault($mageFile)
    {
        $structure = array(
            'app' => array(
                'Mage.php' => file_get_contents($mageFile),
            ),
        );
        vfsStream::setup('root', null, $structure);

        $config = new ProjectConfig(
            // the patch flag is not declared on purpose
            array(
                ProjectConfig::MAGENTO_ROOT_DIR_KEY => vfsStream::url('root'),
            ),
            array()
        );

        $patcher = new Bootstrap($config);
        $patcher->patch();

        $this->assertBootstrapWasNotApplied($mageFile);
    }

    private function assertBootstrapWasNotApplied($mageFile)
    {
        $this->assertFileExists(vfsStream::url('root/app/Mage.php'));
        $this->assertFileEquals(
            $mageFile,
            vfsStream::url('root/app/Mage.php'),
            'File should not be modified but it is'
        );
        $this->assertFileNotExists(vfsStream::url('root/app/bootstrap.php'));
        $this->assertFileNotExists(vfsStream::url('root/app/Mage.class.php'));
        $this->assertFileNotExists(vfsStream::url('root/app/Mage.bootstrap.php'));
        $this->assertFileNotExists(vfsStream::url('root/app/Mage.nonsense.php'));
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
