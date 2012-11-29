<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class SymlinkTest extends AbstractTest
{
    /**
     * @return DeploystrategyAbstract
     */
    public function getTestDeployStrategy($dest, $src)
    {
        return new Symlink($dest, $src);
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

    public function testClean()
    {
        $src = 'local.xml';
        $dest = 'local2.xml';
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $src);
        $this->assertTrue(is_readable($this->sourceDir . DIRECTORY_SEPARATOR . $src));
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
        $this->strategy->create($src, $dest);
        $this->assertTrue(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
        unlink($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->strategy->clean($this->destDir . DIRECTORY_SEPARATOR . $dest);
        $this->assertFalse(is_readable($this->destDir . DIRECTORY_SEPARATOR . $dest));
    }

    public function testChangeLink()
    {
        $wrong_file = 'wrong';
        $right_file = 'right';
        $link = 'link';

        touch($this->sourceDir . DIRECTORY_SEPARATOR . $wrong_file);
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $right_file);
        @unlink($this->destDir . DIRECTORY_SEPARATOR . $link);

        symlink($this->sourceDir . DIRECTORY_SEPARATOR . $wrong_file, $this->destDir . DIRECTORY_SEPARATOR . $link);
        $this->assertEquals($this->sourceDir . DIRECTORY_SEPARATOR . $wrong_file, readlink($this->destDir . DIRECTORY_SEPARATOR . $link));

        $this->strategy->create($right_file, $link);
        $this->assertEquals($this->sourceDir . DIRECTORY_SEPARATOR . $right_file, readlink($this->destDir . DIRECTORY_SEPARATOR . $link));

    }

    public function testGlobLinkTargetDirExists()
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
        $this->assertTrue(
            is_link($testTarget), "Failed asserting that file is a symbolic link"
        );
    }

    public function testGlobLinkTargetDirDoesNotExists()
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
            is_link($testTarget), "Failed asserting that file is a symbolic link"
        );
    }

    public function testGlobLinkSlashDirectoryExists()
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
            is_link($testTarget), "Failed asserting that file is a symbolic link"
        );
    }

    public function testGlobLinkSlashDirectoryDoesNotExists()
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
            is_link($testTarget), "Failed asserting that file is a symbolic link"
        );
    }

    public function testGlobLinkWildcardTargetDirDoesNotExist()
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
                is_link($testTarget), "Failed asserting that file is a symbolic link"
            );
        }
    }

    public function testGlobLinkWildcardTargetDirDoesExist()
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
                is_link($testTarget), "Failed asserting that file is a symbolic link"
            );
        }
    }
}
