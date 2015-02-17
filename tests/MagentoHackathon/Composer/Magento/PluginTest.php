<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Package\RootPackage;
use org\bovigo\vfs\vfsStream;

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
            ->setMethods(array('getEventManager'))
            ->getMock();
    }

    public function testInitDeployManagerRegistersGitIgnoreListenerIfConfigPermits()
    {
        $eventManager = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');

        $this->plugin
            ->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));

        $rootPackage = $this->createRootPackage(array(
            ProjectConfig::AUTO_APPEND_GITIGNORE_KEY => true
        ));

        $this->composer->setPackage($rootPackage);

        $eventManager
            ->expects($this->once())
            ->method('listen')
            ->with('post-package-deploy', $this->isInstanceOf('MagentoHackathon\Composer\Magento\GitIgnoreListener'));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testDebugListenerIsAttached()
    {
        $eventManager = $this->getMock('MagentoHackathon\Composer\Magento\Event\EventManager');

        $this->plugin
            ->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));

        $this->io
            ->expects($this->any())
            ->method('isDebug')
            ->will($this->returnValue(true));

        $this->composer->setPackage($this->createRootPackage());

        $eventManager
            ->expects($this->once())
            ->method('listen')
            ->with('pre-package-deploy', $this->isInstanceOf('Closure'));

        $this->plugin->activate($this->composer, $this->io);
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
}
