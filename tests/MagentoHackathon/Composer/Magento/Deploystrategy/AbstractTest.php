<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
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
        $this->filesystem = new \Composer\Util\Filesystem();
        $this->sourceDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "module_dir";
        $this->destDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "magento_dir";
        $this->filesystem->ensureDirectoryExists($this->sourceDir);
        $this->filesystem->ensureDirectoryExists($this->destDir);
        $this->strategy = $this->getTestDeployStrategy($this->destDir, $this->sourceDir);
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
        $mappingData = array('test', 'test2');
        $this->strategy->setMappings(array($mappingData));
        $this->assertTrue(is_array($this->strategy->getMappings()));
        $first_value = $this->strategy->getMappings();
        $this->assertEquals(array_pop($first_value), $mappingData);
    }

    public function testAddMapping()
    {
        $this->strategy->setMappings(array());
        $this->strategy->addMapping('t1', 't2');
        $this->assertTrue(is_array($this->strategy->getMappings()));
        $first_value = $this->strategy->getMappings();
        $this->assertEquals(array_pop($first_value), array("t1", "t2"));
    }

    /**
     * @abstract
     * @param $dest
     * @param $src
     * @return DeploystrategyAbstract
     */
    abstract function getTestDeployStrategy($dest, $src);
}
