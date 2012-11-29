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
     * @abstract
     * @param $dest
     * @param $src
     * @return DeploystrategyAbstract
     */
    abstract function getTestDeployStrategy($dest, $src);

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

    public function testCreate()
    {
        $src = 'local.xml';
        $dest = 'local2.xml';
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $src);
        $this->assertTrue(is_readable($this->sourceDir . DIRECTORY_SEPARATOR . $src));
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
        $this->strategy->create($src, $dest);
        $this->assertTrue(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
    }

    /**
     *
     */
    public function testCopyDirToDir()
    {
        $src = "hello";
        $dest = "hello2";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $src);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $src . DIRECTORY_SEPARATOR . "local.xml");
        $this->assertTrue(is_readable($this->sourceDir . DIRECTORY_SEPARATOR . $src . DIRECTORY_SEPARATOR . "local.xml"));
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . "local.xml"));
        $this->strategy->create($src, $dest);
        $this->assertTrue(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . "local.xml"));
    }

    public function testGlobTargetDirExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir"; // this dir should contain the target
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $glob_dest, 0777, true);

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . DIRECTORY_SEPARATOR . basename($glob_source);

        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target parent dir is a directory"
        );
        $this->assertFileExists($testTarget);
    }

    public function testGlobTargetDirDoesNotExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir"; // this will be the target!

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest;

        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target parent dir is a directory"
        );
        $this->assertFileExists($testTarget);
    }

    public function testGlobSlashDirectoryExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . dirname(DIRECTORY_SEPARATOR . $glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir/";
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $glob_dest, 0777, true);

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . basename($glob_source);

        // second create has to identify symlink
        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target dir is a directory"
        );
        $this->assertFileExists($testTarget);
    }

    public function testGlobSlashDirectoryDoesNotExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . dirname(DIRECTORY_SEPARATOR . $glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir/"; // the target should be created inside this dir because of the slash

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . basename($glob_source);

        // second create has to identify symlink
        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target parent dir is a directory"
        );
        $this->assertFileExists($testTarget);
    }

    public function testGlobWildcardTargetDirDoesNotExist()
    {
        $glob_source = "sourcedir/*";
        $glob_dir = dirname($glob_source);
        $files = array('test1.xml', 'test2.xml');
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir, 0777, true);
        foreach ($files as $file) {
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir . DIRECTORY_SEPARATOR . $file);
        }

        $glob_dest = "targetdir";

        $this->strategy->create($glob_source, $glob_dest);

        $targetDir = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest;
        $this->assertFileExists($targetDir);
        $this->assertTrue(
            is_dir($targetDir), "Failed asserting target parent dir is a directory"
        );

        foreach ($files as $file) {
            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($testTarget);
        }
    }

    public function testGlobWildcardTargetDirDoesExist()
    {
        $glob_source = "sourcedir/*";
        $glob_dir = dirname($glob_source);
        $files = array('test1.xml', 'test2.xml');
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir, 0777, true);
        foreach ($files as $file) {
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir . DIRECTORY_SEPARATOR . $file);
        }

        $glob_dest = "targetdir";
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $glob_dest);

        $this->strategy->create($glob_source, $glob_dest);

        $targetDir = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest;
        $this->assertFileExists($targetDir);
        $this->assertTrue(
            is_dir($targetDir), "Failed asserting target parent dir is a directory"
        );

        foreach ($files as $file) {
            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($testTarget);
        }
    }
}
