<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

if (! defined('DS')) define('DS', DIRECTORY_SEPARATOR);

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
        $this->sourceDir = sys_get_temp_dir() . DS . $this->getName() . DS . "module_dir";
        $this->destDir = sys_get_temp_dir() . DS . $this->getName() . DS . "magento_dir";
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
     * @throws \InvalidArgumentException
     * @throws \PHPUnit_Framework_AssertionFailedError
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
            //echo "\n$file\n";
            //passthru("ls -l " . $file);
            if (is_dir($file) && ! is_link($file)) {
                $realType = 'dir';
            } elseif (is_link($file)) {
                $realType = 'link';
            } elseif (is_file($file) && ! is_link($file)) {
                $realType = 'file';
            } else {
                $realType = 'unknown';
            }
            throw new \PHPUnit_Framework_AssertionFailedError(
              "Failed to assert that the $file is of type $type, found type $realType instead."
            );
        }
    }

    protected function mkdir($dir, $recursive = true)
    {
        if (file_exists($dir)) {
            if (is_dir($dir)) {
                return true;
            } else {
                throw new \Exception("mkdir('$dir') already exists and is a file");
            }
        }
        return mkdir($dir, 0777, $recursive);
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
        $src = 'local1.xml';
        $dest = 'local2.xml';
        touch($this->sourceDir . DS . $src);
        $this->assertTrue(is_readable($this->sourceDir . DS . $src));
        $this->assertFalse(is_readable($this->destDir . DS . $dest));
        $this->strategy->setCurrentMapping(array($src, $dest));
        $this->strategy->create($src, $dest);
        $this->assertTrue(is_readable($this->destDir . DS . $dest));
    }

    public function testCopyDirToDir()
    {
        $src = "hello1";
        $dest = "hello2";
        $this->mkdir($this->sourceDir . DS . $src);
        touch($this->sourceDir . DS . $src . DS . "local.xml");
        $this->assertTrue(is_readable($this->sourceDir . DS . $src . DS . "local.xml"));
        $this->assertFalse(is_readable($this->destDir . DS . $dest . DS . "local.xml"));
        $this->strategy->setCurrentMapping(array($src, $dest));
        $this->strategy->create($src, $dest);
        $this->assertTrue(is_readable($this->destDir . DS . $dest . DS . "local.xml"));
    }

    public function testGlobTargetDirExists()
    {
        $globSource = "sourcedir/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        touch($this->sourceDir . DS . $globSource);

        $dest = "targetdir"; // this dir should contain the target
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource);

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testTargetDirWithChildDirExists()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest . DS . basename($globSource));

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);
        //passthru("tree {$this->destDir}/$dest");

        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, self::TEST_FILETYPE_FILE);
    }

    public function testTargetDirWithChildDirNotExists()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);
        //passthru("tree {$this->destDir}/$dest");

        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, self::TEST_FILETYPE_FILE);
    }

    public function testGlobTargetDirDoesNotExists()
    {
        $globSource = "sourcedir/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        touch($this->sourceDir . DS . $globSource);

        $dest = "targetdir"; // this will be the target!

        $testTarget = $this->destDir . DS . $dest;

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobSlashDirectoryExists()
    {
        $globSource = "sourcedir/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        touch($this->sourceDir . DS . $globSource);

        $dest = "targetdir/";
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $dest . basename($globSource);

        // second create has to identify symlink
        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);

        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_DIR);
        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());
    }

    public function testGlobSlashDirectoryDoesNotExists()
    {
        $globSource = "sourcedir/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        touch($this->sourceDir . DS . $globSource);

        $dest = "targetdir/"; // the target should be created inside this dir because of the slash

        $testTarget = $this->destDir . DS . $dest . basename($globSource);

        // second create has to identify symlink
        $this->strategy->setCurrentMapping(array($globSource, $dest));
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
        $this->mkdir($this->sourceDir . DS . $glob_dir);
        foreach ($files as $file) {
            touch($this->sourceDir . DS . $glob_dir . DS . $file);
        }

        $dest = "targetdir";

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);

        $targetDir = $this->destDir . DS . $dest;
        $this->assertFileExists($targetDir);
        $this->assertFileType($targetDir, self::TEST_FILETYPE_DIR);


        foreach ($files as $file) {
            $testTarget = $this->destDir . DS . $dest . DS . $file;
            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype());

        }
    }

    public function testGlobWildcardTargetDirDoesExist()
    {
        $globSource = "sourcedir/*";
        $glob_dir = dirname($globSource);
        $files = array('test1.xml', 'test2.xml');
        $this->mkdir($this->sourceDir . DS . $glob_dir);
        foreach ($files as $file) {
            touch($this->sourceDir . DS . $glob_dir . DS . $file);
        }

        $dest = "targetdir";
        $this->mkdir($this->destDir . DS . $dest);

        $this->strategy->setCurrentMapping(array($globSource, $dest));
        $this->strategy->create($globSource, $dest);

        $targetDir = $this->destDir . DS . $dest;
        $this->assertFileExists($targetDir);
        $this->assertFileType($targetDir, self::TEST_FILETYPE_DIR);


        foreach ($files as $file) {
            $testTarget = $this->destDir . DS . $dest . DS . $file;
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
            $this->mkdir($this->sourceDir . DS . $globSource);
            touch($this->sourceDir . DS . $globSource . DS . $sourceDirContent);

            // The target should be created AS THE THIS DIRECTORY because the target dir doesn't exist

            $testTarget = $this->destDir . DS . $dest;
            $testTargetContent = $testTarget . DS . $sourceDirContent;

            $this->strategy->setCurrentMapping(array($globSource, $dest));
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
            $this->mkdir($this->sourceDir . DS . $globSource);
            touch($this->sourceDir . DS . $globSource . DS . $sourceDirContent);

            $this->mkdir($this->destDir . DS . $dest);

            // The target should be created INSIDE the target directory because the target dir exists exist
            // This is how bash commands (and therefore modman) process source and targer

            $testTarget = $this->destDir . DS . $dest . DS . basename($globSource);
            $testTargetContent = $testTarget . DS . $sourceDirContent;

            $this->strategy->setCurrentMapping(array($globSource, $dest));
            $this->strategy->create($globSource, $dest);

            $this->assertFileExists($testTarget);
            $this->assertFileType($testTarget, $this->getTestDeployStrategyFiletype(self::TEST_FILETYPE_DIR));

            $this->assertFileExists($testTargetContent);
            $this->assertFileType($testTargetContent, self::TEST_FILETYPE_FILE);
        }
    }
}
