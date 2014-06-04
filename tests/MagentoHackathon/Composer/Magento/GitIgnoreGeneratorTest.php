<?php
namespace MagentoHackathon\Composer\Magento;

require_once(__DIR__ . '/ModuleInstallerTest.php');

use MagentoHackathon\Composer\Magento\Installer\ModuleInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\Util\Filesystem;
use Composer\Test\TestCase;
use Composer\Composer;
use Composer\Config;

class GitIgnoreGeneratorTestModule extends ModuleInstallerTest
{
    
    protected function getGitIgnoreTestPath()
    {
        return $this->magentoDir . '/.gitignore';
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::appendGitIgnore
     */
    public function testGitIgnoreAppendToExistingFile()
    {
        $gitIgnoreFile      = $this->getGitIgnoreTestPath();
        $gitIgnoreContent   = array("vendor", ".idea");
        file_put_contents($gitIgnoreFile, implode("\n", $gitIgnoreContent));

        $map = array(
            array('test1', 'test1'),
            array('testfolder1/testfile1', 'testfolder1/testfile1'),
        );
        $package = $this->createPackageMock(array('map' => $map, 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);
        $installer = new ModuleInstaller($this->io, $this->composer);
        $installer->appendGitIgnore($package, $gitIgnoreFile);

        $this->assertFileExists($gitIgnoreFile);
        $expectedContent = sprintf("vendor\n.idea\n#%s\n/test1\n/testfolder1/testfile1", $package->getName());
        $this->assertSame(file_get_contents($gitIgnoreFile), $expectedContent);
        unlink($gitIgnoreFile);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::appendGitIgnore
     */
    public function testGitIgnoreCreateFileIfNotExist()
    {
        $gitIgnoreFile = $this->getGitIgnoreTestPath();
        $map = array(
            array('test1', 'test1'),
            array('testfolder1/testfile1', 'testfolder1/testfile1'),
        );
        $package = $this->createPackageMock(array('map' => $map, 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);
        $installer = new ModuleInstaller($this->io, $this->composer);
        $installer->appendGitIgnore($package, $gitIgnoreFile);

        $this->assertFileExists($gitIgnoreFile);
        $expectedContent = sprintf("#%s\n/test1\n/testfolder1/testfile1", $package->getName());
        $this->assertSame(file_get_contents($gitIgnoreFile), $expectedContent);
        unlink($gitIgnoreFile);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::install
     */
    public function testGitAppendMethodNotCalledIfOptionNotSelected()
    {
        $package = $this->createPackageMock(array('map' => array()));
        $this->composer->setPackage($package);

        $mockInstaller = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Installer\ModuleInstaller')
            ->setConstructorArgs(array($this->io, $this->composer))
            ->setMethods(array('appendGitIgnore'))
            ->getMock();
        
        $mockInstaller->setDeployManager( new DeployManager( $this->io ) );

        $mockInstaller->expects($this->never())
            ->method('appendGitIgnore');

        $mockInstaller->install($this->repository, $package);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::install
     */
    public function testGitAppendMethodCalledIfOptionSelected()
    {
        $gitIgnoreFile = $this->getGitIgnoreTestPath();

        $package = $this->createPackageMock(array('map' => array(), 'auto-append-gitignore' => true));
        $this->composer->setPackage($package);

        $mockInstaller = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Installer\ModuleInstaller')
            ->setConstructorArgs(array($this->io, $this->composer))
            ->setMethods(array('getGitIgnoreFileLocation', 'appendGitIgnore'))
            ->getMock();

        $mockInstaller->expects($this->once())
            ->method('getGitIgnoreFileLocation')
            ->will($this->returnValue($gitIgnoreFile));

        $mockInstaller->expects($this->once())
            ->method('appendGitIgnore')
            ->with($package, $gitIgnoreFile);

        $mockInstaller->setDeployManager( new DeployManager( $this->io ) );

        $mockInstaller->install($this->repository, $package);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::getGitIgnoreFileLocation
     */
    public function testGetGitIgnoreFileLocationIsCorrectIfExists()
    {
        $gitignoreFile = $this->getGitIgnoreTestPath();
        touch($gitignoreFile);
        $installer = new ModuleInstaller($this->io, $this->composer);
        $this->assertSame($installer->getGitIgnoreFileLocation(), $gitignoreFile);
        unlink($gitignoreFile);
    }

    /**
     * @covers MagentoHackathon\Composer\Magento\Installer\ModuleInstaller::getGitIgnoreFileLocation
     */
    public function testGetGitIgnoreFileLocationIsCorrectIfNotExists()
    {
        $gitignoreFile = $this->getGitIgnoreTestPath();
        $installer = new ModuleInstaller($this->io, $this->composer);
        $this->assertSame($installer->getGitIgnoreFileLocation(), $gitignoreFile);
    }
}