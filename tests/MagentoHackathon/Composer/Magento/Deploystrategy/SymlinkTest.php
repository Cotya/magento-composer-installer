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

        touch($this->sourceDir.DIRECTORY_SEPARATOR.$wrong_file);
        touch($this->sourceDir.DIRECTORY_SEPARATOR.$right_file);
        @unlink($this->destDir.DIRECTORY_SEPARATOR.$link);

        symlink($this->sourceDir.DIRECTORY_SEPARATOR.$wrong_file, $this->destDir.DIRECTORY_SEPARATOR.$link);
        $this->assertEquals( $this->sourceDir.DIRECTORY_SEPARATOR.$wrong_file, readlink($this->destDir.DIRECTORY_SEPARATOR.$link) );

        $this->strategy->create( $right_file, $link );
        $this->assertEquals( $this->sourceDir.DIRECTORY_SEPARATOR.$right_file, readlink($this->destDir.DIRECTORY_SEPARATOR.$link) );

    }

    public function testGlobLink()
    {
        $glob_source = "modules/test.xml";
        mkdir( $this->sourceDir.dirname(DIRECTORY_SEPARATOR.$glob_source) );
        touch( $this->sourceDir.DIRECTORY_SEPARATOR.$glob_source );

        $glob_dest = "modules";
        mkdir( $this->destDir.DIRECTORY_SEPARATOR.$glob_dest );

        $this->strategy->create($glob_source,$glob_dest);

        $this->assertFileExists( $this->destDir.DIRECTORY_SEPARATOR.$glob_dest );
    }

    public function testGlobLinkSlash()
    {
        $glob_source = "modules/test.xml";
        mkdir( $this->sourceDir.dirname(DIRECTORY_SEPARATOR.$glob_source) );
        touch( $this->sourceDir.DIRECTORY_SEPARATOR.$glob_source );

        $glob_dest = "modules/";

        // first create will create symlink
        $this->strategy->create($glob_source, $glob_dest);

        // second create has to identify symlink
        $this->strategy->create($glob_source, $glob_dest);

        $this->assertFileExists( $this->destDir.DIRECTORY_SEPARATOR.$glob_dest );
    }


}
