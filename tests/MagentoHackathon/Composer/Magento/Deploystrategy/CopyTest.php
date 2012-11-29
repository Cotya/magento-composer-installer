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

    public function testGlobLink()
    {
        $glob_source = "modules/test.xml";
        mkdir($this->sourceDir . dirname(DIRECTORY_SEPARATOR . $glob_source));
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "modules";
        mkdir($this->destDir . DIRECTORY_SEPARATOR . $glob_dest);

        $this->strategy->create($glob_source, $glob_dest);

        $this->assertFileExists($this->destDir . DIRECTORY_SEPARATOR . $glob_dest);
    }

    public function testGlobLinkSlash()
    {
        $glob_source = "modules/test.xml";
        mkdir($this->sourceDir . dirname(DIRECTORY_SEPARATOR . $glob_source));
        touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_source);

        $glob_dest = "modules/";

        // first create will create file
        $this->strategy->create($glob_source, $glob_dest);

        // second create has to identify file
        $this->strategy->create($glob_source, $glob_dest);

        $this->assertFileExists($this->destDir . DIRECTORY_SEPARATOR . $glob_dest);
    }

    public function testGlobLinkWildcard()
    {
        $glob_source = "modules/*";
        $glob_dir = dirname($glob_source);
        $files = array('test1.xml', 'test2.xml');
        mkdir($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir);
        foreach ($files as $file) {
            touch($this->sourceDir . DIRECTORY_SEPARATOR . $glob_dir . DIRECTORY_SEPARATOR . $file);
        }

        $glob_dest = "modules/";

        $this->strategy->create($glob_source, $glob_dest);

        foreach ($files as $file) {
            $this->assertFileExists($this->destDir . DIRECTORY_SEPARATOR . $glob_dest . DIRECTORY_SEPARATOR . $file);
        }
    }
}