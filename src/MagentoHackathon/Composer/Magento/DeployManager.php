<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento;


use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;

class DeployManager {

    /**
     * @var Entry[]
     */
    protected $packages = [];

    /**
     * @var IOInterface
     */
    protected $io;
    
    public function __construct( IOInterface $io )
    {
        $this->io = $io;
    }
    
    
    public function addPackage( Entry $package )
    {
        $this->packages[] = $package;
    }
    
    
    public function doDeploy()
    {
        /** @var Entry $package */
        foreach( $this->packages as $package ){
            if( $this->io->isDebug() ){
                $this->io->write('start magento deploy for '. $package->getPackageName() );
            }
            $package->getDeployStrategy()->deploy();
        }
    }

} 