<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class SymlinkTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Symlink
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Symlink($src, $dest);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        return self::TEST_FILETYPE_LINK;
    }

    public function testClean()
    {
        $src = 'local1.xml';
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
        $wrongFile  = sprintf('%s/%s', $this->sourceDir, 'wrong');
        $rightFile  = sprintf('%s/%s', $this->sourceDir, 'right');
        $link       = sprintf('%s/link', $this->destDir);

        touch($wrongFile);
        touch($rightFile);
        @unlink($link);

        symlink($wrongFile, $link);
        $this->assertEquals($wrongFile, $this->replaceSlashes(readlink($link)));

        $this->strategy->create(basename($rightFile), basename($link));
        $this->assertEquals($this->replaceSlashes(realpath($rightFile)), $this->replaceSlashes(readlink($link)));
    }

    public function testTargetDirWithChildDirExists()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest);
        $this->mkdir($this->destDir . DS . $dest . DS . basename($globSource));

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $this->strategy->setIsForced(false);
        $this->setExpectedException('ErrorException', "Target targetdir/childdir already exists");
        $this->strategy->create($globSource, $dest);
        //passthru("tree {$this->destDir}/$dest");
    }

    public function testTargetDirWithChildDirExistsForce()
    {
        $globSource = 'sourcedir/childdir';
        $sourceContents = "$globSource/test.xml";
        $this->mkdir($this->sourceDir . DS . dirname($globSource));
        $this->mkdir($this->sourceDir . DS . $globSource);
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "targetdir"; // this dir should contain the target child dir
        $this->mkdir($this->destDir . DS . $dest);
        $this->mkdir($this->destDir . DS . $dest . DS . basename($globSource));

        $testTarget = $this->destDir . DS . $dest . DS . basename($globSource) . DS . basename($sourceContents);

        $this->strategy->setIsForced(true);
        $this->strategy->create($globSource, $dest);
        //passthru("tree {$this->destDir}/$dest");

        $this->assertFileExists($testTarget);
        $this->assertFileType($testTarget, self::TEST_FILETYPE_FILE);
        $this->assertFileType(dirname($testTarget), self::TEST_FILETYPE_LINK);
    }

    /**
     * @see https://github.com/magento-hackathon/magento-composer-installer/issues/121
     */
    public function testEmptyDirectoryCleanup()
    {
        $directory  = '/app/code/Jay/Ext1';
        $file       = $directory . '/file.txt';
        $this->mkdir($this->sourceDir . $directory);
        touch($this->sourceDir . $file);
        $this->strategy->setMappings(array(array($file, $file)));
        
        $this->strategy->deploy();
        
        $this->assertFileExists($this->destDir . $file);

        $this->strategy->clean();
        
        $this->assertFileNotExists($this->destDir . $file);
        $this->assertFileNotExists($this->destDir . $directory);
    }

    public function testDeployedFilesAreStored()
    {
        $source         = sprintf('%s/local1.xml', $this->sourceDir);
        $destination    = sprintf('%s/local2.xml', $this->destDir);
        touch($source);
        $this->assertTrue(is_readable($source));
        $this->assertFalse(is_readable($destination));
        $this->strategy->create('local1.xml', 'local2.xml');
        $this->assertTrue(is_readable($destination));
        unlink($destination);
        $this->strategy->clean($destination);
        $this->assertFalse(is_readable($destination));

        $this->assertSame(
            array('/local2.xml'),
            $this->strategy->getDeployedFiles()
        );
    }

    public function testGlobFileResultsDoNotContainDoubleSlashesWhenDestinationDirectoryExists()
    {
        $this->mkdir(sprintf('%s/app/etc/modules/', $this->sourceDir));
        $this->mkdir(sprintf('%s/app/etc/modules', $this->destDir));
        touch(sprintf('%s/app/etc/modules/EcomDev_PHPUnit.xml', $this->sourceDir));
        touch(sprintf('%s/app/etc/modules/EcomDev_PHPUnitTest.xml', $this->sourceDir));

        $this->strategy->create('/app/etc/modules/*.xml', '/app/etc/modules/');

        $expected = array(
            '/app/etc/modules/EcomDev_PHPUnit.xml',
            '/app/etc/modules/EcomDev_PHPUnitTest.xml',
        );

        $result = $this->strategy->getDeployedFiles();
        sort($expected);
        sort($result);
        $this->assertEquals($expected, $result);
    }
}
