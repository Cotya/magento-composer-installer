<?php

namespace MagentoHackathon\Composer\Magento\Repository;

use org\bovigo\vfs\vfsStream;

/**
 * Class InstalledFilesFilesystemRepositoryTest
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledFilesFilesystemRepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var InstalledFilesFilesystemRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $filePath;
    protected $root;

    public function setUp()
    {
        $this->root         = vfsStream::setup('root');
        $this->filePath     = vfsStream::url('root/mappings.json');
        $this->repository   = new InstalledFilesFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotWritable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0400);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not writable');
        new InstalledFilesFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotReadable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0200);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not readable');
        new InstalledFilesFilesystemRepository($this->filePath);
    }

    public function testExceptionIsThrownIfDbDoesNotExistAndFolderIsNotWritable()
    {
        chmod(dirname($this->filePath), 0400);
        $this->setExpectedException('Exception', 'Directory "vfs://root" is not writable');
        new InstalledFilesFilesystemRepository($this->filePath);
    }

    public function testGetInstalledMappingsThrowsExceptionIfPackageNotFound()
    {
        $this->setExpectedException('Exception', 'Package Installed Files for: "not-here" not found');
        $this->repository->getByPackage('not-here');
    }

    public function testGetInstalledMappingsReturnsMappingsCorrectly()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        file_put_contents($this->filePath, json_encode(array('some-package' => $mappings)));
        $this->assertEquals($mappings, $this->repository->getByPackage('some-package'));
    }

    public function testExceptionIsThrownIfDuplicatePackageIsAdded()
    {
        $this->setExpectedException('Exception', 'Package Installed Files for: "some-package" are already present');
        $this->repository->addByPackage('some-package', array());
        $this->repository->addByPackage('some-package', array());
    }

    public function testAddInstalledMappings()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        $this->repository->addByPackage('some-package', $mappings);
        unset($this->repository);
        $this->assertEquals(array('some-package' => $mappings), json_decode(file_get_contents($this->filePath), true));
    }

    public function testExceptionIsThrownIfRemovingMappingsWhichDoNotExist()
    {
        $this->setExpectedException('Exception', 'Package Installed Files for: "some-package" not found');
        $this->repository->removeByPackage('some-package', array());
    }

    public function testCanSuccessfullyRemovePackageMappings()
    {
        $this->repository->addByPackage('some-package', array());
        $this->repository->removeByPackage('some-package', array());
    }

    public function testFileIsNotWrittenIfNoChanges()
    {
        $mappings = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
        );

        file_put_contents($this->filePath, json_encode(array('some-package' => $mappings)));
        $writeTime = filemtime($this->filePath);
        unset($this->repository);
        clearstatcache();

        $this->assertEquals($writeTime, filemtime($this->filePath));
    }

    public function tearDown()
    {
        unset($this->repository);
    }
}
