<?php

namespace MagentoHackathon\Composer\Magento\Repository;

use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\InstalledPackageDumper;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Yaml\Dumper;

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
        $this->repository   = new InstalledPackageFileSystemRepository($this->filePath, new InstalledPackageDumper);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotWritable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0400);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not writable');
        new InstalledPackageFilesystemRepository($this->filePath, new InstalledPackageDumper);
    }

    public function testExceptionIsThrownIfDbFileExistsButIsNotReadable()
    {
        vfsStream::newFile('mappings.json')->at($this->root);
        chmod($this->filePath, 0200);
        $this->setExpectedException('Exception', 'File "vfs://root/mappings.json" is not readable');
        new InstalledPackageFilesystemRepository($this->filePath, new InstalledPackageDumper);
    }

    public function testExceptionIsThrownIfDbDoesNotExistAndFolderIsNotWritable()
    {
        chmod(dirname($this->filePath), 0400);
        $this->setExpectedException('Exception', 'Directory "vfs://root" is not writable');
        new InstalledPackageFilesystemRepository($this->filePath, new InstalledPackageDumper);
    }

    public function testGetInstalledMappingsThrowsExceptionIfPackageNotFound()
    {
        $this->setExpectedException('Exception', 'Package Installed Files for: "not-here" not found');
        $this->repository->findByPackageName('not-here');
    }

    public function testGetInstalledMappingsReturnsMappingsCorrectly()
    {
        $files = array(
            'file1',
            'file2',
            'file3',
        );

        $data = array(array(
            'packageName' => 'some-package',
            'version' => '1.0.0',
            'installedFiles' => $files,
        ));
        file_put_contents($this->filePath, json_encode($data));
        $package = $this->repository->findByPackageName('some-package');
        $this->assertEquals($files, $package->getInstalledFiles());
        $this->assertEquals('some-package', $package->getName());
        $this->assertInstanceOf('MagentoHackathon\Composer\Magento\InstalledPackage', $package);
    }

    public function testExceptionIsThrownIfDuplicatePackageIsAdded()
    {
        $this->setExpectedException('Exception', 'Package: "some-package" is already installed');

        $package = new InstalledPackage('some-package', '1.0.0', array());
        $this->repository->add($package);
        $this->repository->add($package);
    }

    public function testAddInstalledMappings()
    {
        $files = array(
            'file1',
            'file2',
            'file3',
        );

        $expected = array(array(
            'packageName' => 'some-package',
            'version' => '1.0.0',
            'installedFiles' => $files,
        ));
        $package = new InstalledPackage('some-package', '1.0.0', $files);
        $this->repository->add($package);
        unset($this->repository);
        $this->assertEquals($expected, json_decode(file_get_contents($this->filePath), true));
    }

    public function testExceptionIsThrownIfRemovingMappingsWhichDoNotExist()
    {
        $this->setExpectedException('Exception', 'Package: "some-package" not found');
        $this->repository->remove(new InstalledPackage('some-package', '1.0.0', array()));
    }

    public function testCanSuccessfullyRemovePackageMappings()
    {
        $package = new InstalledPackage('some-package', '1.0.0', array());
        $this->repository->add($package);
        $this->repository->remove($package);
    }

    public function testFileIsNotWrittenIfNoChanges()
    {
        $files = array(
            'file1',
            'file2',
            'file3',
        );

        $expected = array(array(
            'packageName' => 'some-package',
            'installedFiles' => $files,
        ));

        file_put_contents($this->filePath, json_encode($expected));
        $writeTime = filemtime($this->filePath);
        unset($this->repository);
        clearstatcache();

        $this->assertEquals($writeTime, filemtime($this->filePath));
    }

    public function testFindAllPackages()
    {
        $this->assertEmpty($this->repository->findAll());
        $package = new InstalledPackage('some-package', '1.0.0', array());
        $this->repository->add($package);
        $this->assertCount(1, $this->repository->findAll());
        $this->assertSame(array($package), $this->repository->findAll());
    }

    public function tearDown()
    {
        unset($this->repository);
    }
}
