<?php

use Composer\Util\Filesystem;
use MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategy;

/**
 * Class UnInstallStrategyTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UnInstallStrategyTest extends PHPUnit_Framework_TestCase
{
    protected $testDirectory;

    public function testUnInstall()
    {
        $this->testDirectory    = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        $this->testDirectory    = str_replace('\\', '/', $this->testDirectory);
        $rootDir                = $this->testDirectory . '/root';
        mkdir($rootDir, 0777, true);

        $strategy   = new UnInstallStrategy(new FileSystem, $rootDir);

        mkdir($rootDir . '/child1/secondlevelchild1', 0777, true);
        mkdir($rootDir . '/child2/secondlevelchild2', 0777, true);
        mkdir($rootDir . '/child3/secondlevelchild3', 0777, true);
        mkdir($rootDir . '/child4/secondlevelchild4', 0777, true);

        touch($rootDir . '/child1/secondlevelchild1/file1.txt');
        touch($rootDir . '/child2/secondlevelchild2/file2.txt');
        touch($rootDir . '/child3/secondlevelchild3/file3.txt');
        touch($rootDir . '/child4/secondlevelchild4/file4.txt');

        $files = [
            '/child1/secondlevelchild1/file1.txt',
            '/child2/secondlevelchild2/file2.txt',
            '/child3/secondlevelchild3/file3.txt',
            '/child4/secondlevelchild4/file4.txt',
        ];

        $strategy->unInstall($files);

        $this->assertFileExists($rootDir);
        $this->assertFileNotExists($rootDir . '/child1');
        $this->assertFileNotExists($rootDir . '/child2');
        $this->assertFileNotExists($rootDir . '/child3');
        $this->assertFileNotExists($rootDir . '/child4');
    }

    public function testUnInstallDoesNotRemoveOtherFiles()
    {
        $this->testDirectory    = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        $this->testDirectory    = str_replace('\\', '/', $this->testDirectory);
        $rootDir                = $this->testDirectory . '/root';
        mkdir($rootDir, 0777, true);

        $strategy   = new UnInstallStrategy(new FileSystem, $rootDir);

        mkdir($rootDir . '/child1/secondlevelchild1', 0777, true);
        mkdir($rootDir . '/child2/secondlevelchild2', 0777, true);
        mkdir($rootDir . '/child3/secondlevelchild3', 0777, true);
        mkdir($rootDir . '/child4/secondlevelchild4', 0777, true);

        touch($rootDir . '/child1/secondlevelchild1/file1.txt');
        touch($rootDir . '/child2/secondlevelchild2/file2.txt');
        touch($rootDir . '/child3/secondlevelchild3/file3.txt');
        touch($rootDir . '/child4/secondlevelchild4/file4.txt');
        touch($rootDir . '/child4/secondlevelchild4/file5.txt');

        $files = [
            '/child1/secondlevelchild1/file1.txt',
            '/child2/secondlevelchild2/file2.txt',
            '/child3/secondlevelchild3/file3.txt',
            '/child4/secondlevelchild4/file4.txt',
        ];

        $strategy->unInstall($files);

        $this->assertFileExists($rootDir);
        $this->assertFileNotExists($rootDir . '/child1');
        $this->assertFileNotExists($rootDir . '/child2');
        $this->assertFileNotExists($rootDir . '/child3');
        $this->assertFileNotExists($rootDir . '/child4/secondlevelchild4/file4.txt');
        $this->assertFileExists($rootDir . '/child4/secondlevelchild4/file5.txt');
    }

    public function tearDown()
    {
        $fs = new Filesystem;
        $fs->remove($this->testDirectory);
    }
}
