<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param $dest
     * @param $src
     * @return Copy
     */
    public function getTestDeployStrategy($dest, $src)
    {
        return new Copy($dest, $src);
    }

    /**
     *
     */
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

    public function testGlobCopyTargetDirExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir"; // this dir should contain the link
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $glob_dest, 0777, true);

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest . DIRECTORY_SEPARATOR . basename($glob_source);

        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target parent dir is a directory"
        );
        $this->assertFileExists($testTarget);
    }

    public function testGlobCopyTargetDirDoesNotExists()
    {
        $glob_source = "sourcedir/test.xml";
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . dirname($glob_source), 0777, true);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "targetdir"; // this will be the link!

        $testTarget = $this->destDir . DIRECTORY_SEPARATOR . $glob_dest;

        $this->strategy->create($glob_source, $glob_dest);

        $this->assertTrue(
            is_dir(dirname($testTarget)), "Failed asserting that the target parent dir is a directory"
        );
        $this->assertFileExists($testTarget);
        $this->assertTrue(
            is_file($testTarget), "Failed asserting that file is a file"
        );
    }

    public function testGlobCopySlashDirectoryExists()
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
        $this->assertTrue(
            is_file($testTarget), "Failed asserting that file is a file"
        );
    }

    public function testGlobCopySlashDirectoryDoesNotExists()
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
        $this->assertTrue(
            is_file($testTarget), "Failed asserting that file is a file"
        );
    }

    public function testGlobCopyWildcardTargetDirDoesNotExist()
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
            $this->assertTrue(
                is_file($testTarget), "Failed asserting that file is a file"
            );
        }
    }

    public function testGlobCopyWildcardTargetDirDoesExist()
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
            $this->assertTrue(
                is_file($testTarget), "Failed asserting that file is a file"
            );
        }
    }
}