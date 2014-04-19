<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\ScriptEvents;

class Plugin implements PluginInterface, EventSubscriberInterface{

    /**
     * @var IOInterface
     */
    protected $io;
    
    
    /**
     * @var DeployManager
     */
    protected $deployManager;
    
    
    protected function initDeployManager(Composer $composer, IOInterface $io)
    {
        $this->deployManager = new DeployManager( $io );
        
        $extra          = $composer->getPackage()->getExtra();
        $sortPriority   = isset($extra['magento-deploy-sort-priority']) ? $extra['magento-deploy-sort-priority'] : [];
        $this->deployManager->setSortPriority( $sortPriority );
        
    }
    

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io  = $io;
        $installer = new Installer($io, $composer);
        $this->initDeployManager($composer, $io);
        $installer->setDeployManager( $this->deployManager );
        if( $this->io->isDebug() ){
            $this->io->write('activate magento plugin');
        }
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onCommandEvent', 0),
            ),
            ScriptEvents::POST_INSTALL_CMD => array(
                array('onNewCodeEvent', 0),
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('onNewCodeEvent', 0),
            ),
        );
    }


    /**
     * actually is triggered before anything got executed
     * 
     * @param \Composer\Plugin\CommandEvent $event
     */
    public function onCommandEvent( \Composer\Plugin\CommandEvent $event )
    {
        $command = $event->getCommandName();
    }

    /**
     * event listener is named this way, as it listens for events leading to changed code files
     * 
     * @param \Composer\Script\CommandEvent $event
     */
    public function onNewCodeEvent( \Composer\Script\CommandEvent $event )
    {
        if( $this->io->isDebug() ){
            $this->io->write('start magento deploy via deployManager');
        }
        $command = $event->getName();
        $this->deployManager->doDeploy();
    }

} 