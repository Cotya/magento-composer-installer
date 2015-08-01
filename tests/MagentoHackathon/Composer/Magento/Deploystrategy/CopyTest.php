<?php
namespace MagentoHackathon\Composer\Magento\Deploystrategy;

class CopyTest extends AbstractTest
{
    /**
     * @param string $src
     * @param string $dest
     * @return Copy
     */
    public function getTestDeployStrategy($src, $dest)
    {
        return new Copy($src, $dest);
    }

    /**
     * @param bool $isDir
     * @return string
     */
    public function getTestDeployStrategyFiletype($isDir = false)
    {
        if ($isDir) {
            return self::TEST_FILETYPE_DIR;
        }

        return self::TEST_FILETYPE_FILE;
    }
    
    public function testCopyDirToDirOfSameName()
    {
        $sourceRoot = 'root';
        $sourceContents = "subdir/subdir/test.xml";

        $this->mkdir(sprintf('%s/%s/%s', $this->sourceDir, $sourceRoot, dirname($sourceContents)));
        touch(sprintf('%s/%s/%s', $this->sourceDir, $sourceRoot, $sourceContents));

        // intentionally using a differnt name to verify solution doesn't rely on identical src/dest paths
        $dest = "dest/root";
        $this->mkdir(sprintf('%s/%s', $this->destDir, $dest));

        $testTarget = $this->destDir . DS . $dest . DS . $sourceContents;
        $this->strategy->setCurrentMapping(array($sourceRoot, $dest));

        $this->strategy->create($sourceRoot, $dest);
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->create($sourceRoot, $dest);

        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));
    }

    public function testWildcardCopyToExistingDir()
    {
        $sourceContents = "app/code/test.php";
        
        //create target directory before
        $this->mkdir(sprintf('%s/app/code', $this->destDir));
        $this->mkdir(sprintf('%s/app/code', $this->sourceDir));
        touch(sprintf('%s/%s', $this->sourceDir, $sourceContents));

        $dest = "dest/root";
        $this->mkdir(sprintf('%s/%s/', $this->destDir, $dest));

        $testTarget = sprintf('%s/%s', $this->destDir, $sourceContents);
        $this->strategy->setMappings(array(array('*', '/')));

        $this->strategy->deploy();
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->deploy();

        $this->assertFileNotExists(sprintf('%s/app/app/code/test.php', $this->destDir));
        
    }

    public function testDeployedFilesAreStored()
    {
        $sourceRoot = 'root';
        $sourceContents = "subdir/subdir/test.xml";

        $this->mkdir($this->sourceDir . DS . $sourceRoot . DS . dirname($sourceContents));
        touch($this->sourceDir . DS . $sourceRoot . DS . $sourceContents);

        // intentionally using a differnt name to verify solution doesn't rely on identical src/dest paths
        $dest = "dest/root";
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $dest . DS . $sourceContents;
        $this->strategy->setCurrentMapping(array($sourceRoot, $dest));

        $this->strategy->setIsForced(true);
        $this->strategy->create($sourceRoot, $dest);

        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));

        $this->assertSame(
            array('/dest/root/subdir/subdir/test.xml'),
            $this->strategy->getDeployedFiles()
        );
    }
}
