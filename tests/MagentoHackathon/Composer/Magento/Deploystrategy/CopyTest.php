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
        if ($isDir) return self::TEST_FILETYPE_DIR;

        return self::TEST_FILETYPE_FILE;
    }
    
    public function testCopyDirToDirOfSameName()
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
        $this->mkdir($this->destDir . DS . 'app' . DS . 'code');

        $this->mkdir($this->sourceDir . DS . dirname($sourceContents));
        touch($this->sourceDir . DS . $sourceContents);

        $dest = "dest/root";
        $this->mkdir($this->destDir . DS . $dest);

        $testTarget = $this->destDir . DS . $sourceContents;
        $this->strategy->setMappings(array(array('*', '/')));

        $this->strategy->deploy();
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->deploy();

        $this->assertFileNotExists($this->destDir . DS . 'app' . DS . 'app' . DS . 'code' . DS . 'test.php');
        
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

        $this->strategy->create($sourceRoot, $dest);
        $this->assertFileExists($testTarget);

        $this->strategy->setIsForced(true);
        $this->strategy->create($sourceRoot, $dest);

        $this->assertFileNotExists(dirname(dirname($testTarget)) . DS . basename($testTarget));

        $this->assertSame(
            array('/dest/root/subdir/subdir/test.xml'),
            $this->strategy->getDeployedFiles()
        );
    }
}
