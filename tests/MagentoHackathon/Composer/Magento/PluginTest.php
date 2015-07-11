<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Package\AliasPackage;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Plugin\CommandEvent;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use org\bovigo\vfs\vfsStream;
use ReflectionObject;

/**
 * Class PluginTest
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{

    protected $composer;
    protected $io;
    protected $config;
    protected $root;
    protected $magentoDir;
    protected $plugin;
    protected $eventManager;

    public function setUp()
    {
        $this->composer = new Composer;
        $this->config = $this->getMock('Composer\Config');
        $this->composer->setConfig($this->config);
        $this->root = vfsStream::setup('root', null, array('vendor' => array('bin' => array()), 'htdocs' => array()));

        $this->config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($value) {
                switch ($value) {
                    case 'vendor-dir':
                        return vfsStream::url('root/vendor');
                    case 'bin-dir':
                        return vfsStream::url('root/vendor/bin');
                }
            }));

        $this->config->expects($this->any())
            ->method('all')
            ->will($this->returnValue(array(
                'repositories' => array(),
                'config' => array(
                    'vendor-dir' => vfsStream::url('root/vendor'),
                    'bin-dir' => vfsStream::url('root/vendor/bin'),
                ),
            )));

        $this->composer->setInstallationManager(new InstallationManager());

        $this->io = $this->getMock('Composer\IO\IOInterface');

        $this->plugin = $this->getMockBuilder('MagentoHackathon\Composer\Magento\Plugin')
            ->setMethods(array('getEventManager', 'getModuleManager'))
            ->getMock();

        $repoManager    = new RepositoryManager($this->io, $this->config);
        $repoManager->setLocalRepository(new WritableArrayRepository);
        $this->composer->setRepositoryManager($repoManager);

        $this->eventManager = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');
        $this->plugin
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($this->eventManager));
    }

    public function testInitDeployManagerRegistersGitIgnoreListenerIfConfigPermits()
    {
        $rootPackage = $this->createRootPackage(array(
            ProjectConfig::AUTO_APPEND_GITIGNORE_KEY => true
        ));

        $this->composer->setPackage($rootPackage);

        $this->eventManager
            ->expects($this->once())
            ->method('listen')
            ->with('post-package-deploy', $this->isInstanceOf('MagentoHackathon\Composer\Magento\GitIgnoreListener'));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testDebugListenerIsAttached()
    {
        $this->io
            ->expects($this->any())
            ->method('isDebug')
            ->will($this->returnValue(true));

        $this->composer->setPackage($this->createRootPackage());

        $this->eventManager
            ->expects($this->once())
            ->method('listen')
            ->with('pre-package-deploy', $this->isInstanceOf('Closure'));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testOnlyMagentoModulePackagesArePassedToModuleManager()
    {
        $this->composer->setPackage($this->createRootPackage());
        $this->plugin->activate($this->composer, $this->io);
        $moduleManagerMock = $this->getMockBuilder('\MagentoHackathon\Composer\Magento\ModuleManager')
            ->disableOriginalConstructor()
            ->setMethods(['updateInstalledPackages'])
            ->getMock();

        $this->plugin
            ->expects($this->any())
            ->method('getModuleManager')
            ->will($this->returnValue($moduleManagerMock));

        $mPackage1      = $this->createPackage('magento/module1', 'magento-module');
        $mPackage2      = $this->createPackage('magento/module2', 'magento-module');
        $normalPackage  = $this->createPackage('normal/module', 'other-module');
        $lRepository    = $this->composer->getRepositoryManager()->getLocalRepository();
        $lRepository->addPackage($mPackage1);
        $lRepository->addPackage($mPackage2);
        $lRepository->addPackage($normalPackage);

        $moduleManagerMock
            ->expects($this->once())
            ->method('updateInstalledPackages')
            ->with([$mPackage1, $mPackage2]);

        $this->plugin->onNewCodeEvent(new \Composer\Script\Event('event', $this->composer, $this->io));
    }

    /**
     * Given Magento Composer Installer is configured to skip repository suggestions
     * When the plugin object is activated
     * Then no suggestions will be given.
     */
    public function testSkipSuggestRepositories()
    {
        $rootPackage = $this->createRootPackage(array(
            ProjectConfig::EXTRA_WITH_SKIP_SUGGEST_KEY => true
        ));
        $this->composer->setPackage($rootPackage);
        $this->io
            ->expects($this->never())
            ->method('write');
        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAliasPackagesAreFilteredOut()
    {
        $this->composer->setPackage($this->createRootPackage());
        $this->plugin->activate($this->composer, $this->io);
        $moduleManagerMock = $this->getMockBuilder('\MagentoHackathon\Composer\Magento\ModuleManager')
            ->disableOriginalConstructor()
            ->setMethods(['updateInstalledPackages'])
            ->getMock();

        $this->plugin
            ->expects($this->any())
            ->method('getModuleManager')
            ->will($this->returnValue($moduleManagerMock));

        $mPackage   = $this->createPackage('magento/module1', 'magento-module');
        $aPackage   = new AliasPackage($mPackage, '99999999-dev', '1.0.x-dev');

        $lRepository    = $this->composer->getRepositoryManager()->getLocalRepository();
        $lRepository->addPackage($mPackage);
        $lRepository->addPackage($aPackage);

        $moduleManagerMock
            ->expects($this->once())
            ->method('updateInstalledPackages')
            ->with([$mPackage]);

        $this->plugin->onNewCodeEvent(new \Composer\Script\Event('event', $this->composer, $this->io));
    }

    /**
     * @param array $extra
     * @return RootPackage
     */
    private function createRootPackage(array $extra = array())
    {
        $package = new RootPackage("root/package", "1.0.0", "root/package");
        $extra['magento-root-dir'] = vfsStream::url('root/htdocs');
        $package->setExtra($extra);
        return $package;
    }

    /**
     * @param string $name
     * @param string $type
     * @return Package
     */
    private function createPackage($name, $type)
    {
        $package = new Package($name, '1.0.0', $name);
        $package->setType($type);
        return $package;
    }
}
