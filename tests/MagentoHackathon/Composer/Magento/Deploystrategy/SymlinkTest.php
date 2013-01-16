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
        $wrongFile = $this->sourceDir . DIRECTORY_SEPARATOR . 'wrong';
        $rightFile = $this->sourceDir . DIRECTORY_SEPARATOR . 'right';
        $link = $this->destDir . DIRECTORY_SEPARATOR . 'link';

        touch($wrongFile);
        touch($rightFile);
        @unlink($link);

        symlink($wrongFile, $link);
        $this->assertEquals($wrongFile, readlink($link));

        $this->strategy->create(basename($rightFile), basename($link));
        $this->assertEquals(realpath($rightFile), realpath(dirname($rightFile) . DIRECTORY_SEPARATOR . readlink($link)));
    }
}
