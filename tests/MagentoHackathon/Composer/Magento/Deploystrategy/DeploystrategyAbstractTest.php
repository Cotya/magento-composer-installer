<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-10-27 at 15:56:01.
 */
abstract class DeploystrategyAbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeploystrategyAbstract
     */
    protected $strategy = null;
    /**
     * @var  string
     */
    protected $sourceDir;

    /**
     * @var string
     */
    protected $destDir;
    /**
     * @var \Composer\Util\Filesystem;
     */
    protected $filesystem;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $mappingData = array();
        $mappingData['test'] = 'test2';
        $this->filesystem = new \Composer\Util\Filesystem();
        $this->sourceDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "module_dir";
        $this->destDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "magento_dir";
        $this->filesystem->ensureDirectoryExists($this->sourceDir);
        $this->filesystem->ensureDirectoryExists($this->destDir);
        $this->strategy = $this->getTestDeployStrategy($this->destDir, $this->sourceDir);
        $this->strategy->setMappings($mappingData);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->filesystem->remove($this->sourceDir);
        $this->filesystem->remove($this->destDir);
    }

    public function testGetMappings()
    {
        $this->assertTrue(is_array($this->strategy->getMappings()));
        $this->assertArrayHasKey('test', $this->strategy->getMappings());
    }

    /**
     * @abstract
     * @return DeploystrategyAbstract
     */
    abstract function getTestDeployStrategy($dest, $src);
}