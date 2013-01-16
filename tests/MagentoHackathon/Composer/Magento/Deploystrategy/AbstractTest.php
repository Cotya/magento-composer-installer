<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    const TEST_FILETYPE_FILE = 'file';
    const TEST_FILETYPE_LINK = 'link';
    const TEST_FILETYPE_DIR  = 'dir';

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
     * @param string $src
     * @param string $dest
     * @return DeploystrategyAbstract
     */
    abstract function getTestDeployStrategy($src, $dest);

    /**
     * @abstract
     * @param bool $isDir
     * @return string
     */
    abstract function getTestDeployStrategyFiletype($isDir = false);

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
        $this->strategy = $this->getTestDeployStrategy($this->sourceDir, $this->destDir);
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

    /**
     * @param string $file
     * @param string $type
     */
    public function assertFileType($file, $type)
    {
        switch ($type) {
            case self::TEST_FILETYPE_FILE:
                $result = is_file($file) && ! is_link($file);
                break;
            case self::TEST_FILETYPE_LINK:
                $file = rtrim($file, '/\\');
                $result = is_link($file);
                break;
            case self::TEST_FILETYPE_DIR:
                $result = is_dir($file) && ! is_link($file);
                break;
            default:
                throw new \InvalidArgumentException(
                    "Invalid file type argument: " . $type
                );
        }
        if (! $result) {
            echo "\n$file\n";
            passthru("ls -l " . $file);
            throw new \PHPUnit_Framework_AssertionFailedError(
              "Failed to assert that the $file is of type $type"
            );
        }
    }

    public function testGetMappings()
    {
        $mappingData = array('test', 'test2');
        $this->strategy->setMappings(array($mappingData));
        $this->assertTrue(is_array($this->strategy->getMappings()));
        $firstValue = $this->strategy->getMappings();
        $this->assertEquals(array_pop($firstValue), $mappingData);
    }

    public function testAddMapping()
    {
        $this->strategy->setMappings(array());
        $this->strategy->addMapping('t1', 't2');
        $this->assertTrue(is_array($this->strategy->getMappings()));
        $firstValue = $this->strategy->getMappings();
        $this->assertEquals(array_pop($firstValue), array("t1", "t2"));
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
        $globSource = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($globSource), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource);

        $dest = "targetdir"; // this dir should contain the target
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $dest, 0777, true);

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . basename($globSource);

        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobTargetDirDoesNotExists()
    {
        $globSource = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($globSource), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource);

        $dest = "targetdir"; // this will be the target!

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest;

        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobSlashDirectoryExists()
    {
        $globSource = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($globSource), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource);

        $dest = "targetdir/";
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $dest, 0777, true);

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . basename($globSource);

        // second create has to identify symlink
        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobSlashDirectoryDoesNotExists()
    {
        $globSource = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($globSource), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource);

        $dest = "targetdir/"; // the target should be created inside this dir because of the slash

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . basename($globSource);

        // second create has to identify symlink
        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobWildcardTargetDirDoesNotExist()
    {
        $globSource = "sourcedir/*";
        $glob_dir = dirname($globSource);
        $files = array('test1.xml', 'test2.xml');
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir, 0777, true);
        foreach ($files as $file) {
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir . DIRECTORY_SEPARATOR . $file);
        }

        $dest = "targetdir";

        $this->strategy->create($globSource, $dest);

        $targetDir = $this->destDir . DIRECTORY_SEPARATOR . $dest;
        $this->assertFileExists($targetDir);
        $this->assertFileType($targetDir, self::TEST_FILETYPE_DIR);


        foreach ($files as $file) {
            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());

        }
    }

    public function testGlobWildcardTargetDirDoesExist()
    {
        $globSource = "sourcedir/*";
        $glob_dir = dirname($globSource);
        $files = array('test1.xml', 'test2.xml');
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir, 0777, true);
        foreach ($files as $file) {
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir . DIRECTORY_SEPARATOR . $file);
        }

        $dest = "targetdir";
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $dest);

        $this->strategy->create($globSource, $dest);

        $targetDir = $this->destDir . DIRECTORY_SEPARATOR . $dest;
        $this->assertFileExists($targetDir);
        $this->assertFileType($targetDir, self::TEST_FILETYPE_DIR);


        foreach ($files as $file) {
            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
        }
    }

    public function testSourceAndTargetAreDirsDoNotExist()
    {
        $fixtures = array(
            array('sourcedir', 'targetdir'),
            array('sourcedir', 'targetdir/'),
            array('sourcedir/', 'targetdir/'),
            array('sourcedir/', 'targetdir'),
        );
        foreach ($fixtures as $fixture) {
            $this->tearDown();
            $this->setUp();

            list ($globSource, $dest) = $fixture;
            $sourceDirContent = "test.xml";
            mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $globSource, 0777, true);
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource . DIRECTORY_SEPARATOR . $sourceDirContent);

            // The target should be created AS THE THIS DIRECTORY because the target dir doesn't exist

            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest;
            $testTargetContent = $testTarget . DIRECTORY_SEPARATOR . $sourceDirContent;

            $this->strategy->create($globSource, $dest);

            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype(self::TEST_FILETYPE_DIR));

            $this->assertFileExists($testTargetContent);
            $this->assertFileType($testTargetContent, self::TEST_FILETYPE_FILE);
        }
    }

    public function testSourceAndTargetAreDirsDoExist()
    {
        $fixtures = array(
            array('sourcedir', 'targetdir'),
            array('sourcedir', 'targetdir/'),
            array('sourcedir/', 'targetdir/'),
            array('sourcedir/', 'targetdir'),
        );
        foreach ($fixtures as $fixture) {
            $this->tearDown();
            $this->setUp();

            list ($globSource, $dest) = $fixture;
            $sourceDirContent = "test.xml";
            mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $globSource, 0777, true);
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $globSource . DIRECTORY_SEPARATOR . $sourceDirContent);

            mkdir($this->destDir . DIRECTORY_SEPARATOR . $dest);

            // The target should be created INSIDE the target directory because the target dir exists exist
            // This is how bash commands (and therefore modman) process source and targer

            $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $dest . DIRECTORY_SEPARATOR . basename($globSource);
            $testTargetContent = $testTarget . DIRECTORY_SEPARATOR . $sourceDirContent;

            $this->strategy->create($globSource, $dest);

            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype(self::TEST_FILETYPE_DIR));

            $this->assertFileExists($testTargetContent);
            $this->assertFileType($testTargetContent, self::TEST_FILETYPE_FILE);
        }
    }
}
