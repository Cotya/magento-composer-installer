<?php

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class NoneTest extends PHPUnit_Framework_TestCase
{
    const URL_VFS_ROOT = 'vfsroot';

    /**
     * @var string
     */
    protected $sourceDir;

    /**
     * @var string
     */
    protected $destDir;

    /**
     * @var \MagentoHackathon\Composer\Magento\Deploystrategy\None
     */
    protected $strategy;

    /**
     * @param string $input
     * @return string
     */
    protected function getVfsUrl($input)
    {
        return vfsStream::url(self::URL_VFS_ROOT . DS . $input);
    }

    protected function setUp()
    {
        vfsStream::setup(self::URL_VFS_ROOT);
        $this->sourceDir = $this->getVfsUrl('sourceDir');
        $this->destDir = $this->getVfsUrl('destDir');
        $this->strategy = new None($this->sourceDir, $this->destDir);
    }

    public function testCreate()
    {
        $src = 'test1';
        $dest = 'test2';

        //create the source directory
        mkdir($this->getVfsUrl('sourceDir' . DS . $src), null, true);

        $this->assertTrue(is_dir($this->getVfsUrl('sourceDir' . DS . $src)));
        $this->assertFalse(is_dir($this->getVfsUrl('destDir' . DS . $dest)));

        //run the none deploy strategy
        $this->strategy->create($src, $dest);

        //check that everything is still the same
        $this->assertTrue(is_dir($this->getVfsUrl('sourceDir' . DS . $src)));
        $this->assertFalse(is_dir($this->getVfsUrl('destDir' . DS . $dest)));
    }

    public function testDeployedFilesIsEmpty()
    {
        $src = 'test1';
        $dest = 'test2';

        //create the source directory
        mkdir($this->getVfsUrl('sourceDir' . DS . $src), null, true);

        $this->assertTrue(is_dir($this->getVfsUrl('sourceDir' . DS . $src)));
        $this->assertFalse(is_dir($this->getVfsUrl('destDir' . DS . $dest)));

        //run the none deploy strategy
        $this->strategy->create($src, $dest);

        //check that everything is still the same
        $this->assertTrue(is_dir($this->getVfsUrl('sourceDir' . DS . $src)));
        $this->assertFalse(is_dir($this->getVfsUrl('destDir' . DS . $dest)));

        $this->assertSame(
            array(),
            $this->strategy->getDeployedFiles()
        );
    }
}
