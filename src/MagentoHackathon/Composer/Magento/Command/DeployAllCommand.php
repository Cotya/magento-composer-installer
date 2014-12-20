<?php
/**
 * 
 * 
 * 
 * 
 */

namespace MagentoHackathon\Composer\Magento\Command;

use MagentoHackathon\Composer\Helper;
use MagentoHackathon\Composer\Magento\DeployManager;
use MagentoHackathon\Composer\Magento\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployAllCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('deploy:all')
            ->setDescription('triggers deploy of all packages')
        ;
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $composerHelper = new Helper(new \SplFileInfo(getcwd()));
        
        foreach($composerHelper->getInstalledPackages() as $package){

        $packageName = $package['name'];
        $output->writeln('<info>deploy '.$packageName.'</info>');
        //var_dump($package);
        
        if ($package['type'] !== 'magento-module') {
            $output->writeln('<comment>this package is not of type "magento module"</comment>');
        } else {
            $packageDir = $composerHelper->getVendorDirectory()->getPathname().'/'.$package['name']; // @todo not secure
            
            $deployStrategy = Factory::getDeployStrategyObject(
                $composerHelper->getMagentoProjectConfig()->getDeployStrategy(),
                $packageDir,
                realpath($composerHelper->getMagentoProjectConfig()->getMagentoRootDir())
            );
            $deployStrategy->setIsForced($composerHelper->getMagentoProjectConfig()->getMagentoForce());
            $mappingParser = Factory::getMappingParser(
                $composerHelper->getMagentoProjectConfig(),
                $package,
                $packageDir
            );
            $deployStrategy->setMappings($mappingParser->getMappings());

            $deployStrategy->deploy();
            
            
        }

        }
    }
}
