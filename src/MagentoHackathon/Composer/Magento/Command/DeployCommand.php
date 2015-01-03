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

class DeployCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('triggers deploy of a package')
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'the package to deploy'
            )
        ;
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');

        $composerHelper = new Helper(new \SplFileInfo(getcwd()));
        $package = $composerHelper->getPackageByName($packageName);
        $output->writeln('deploy '.$packageName);
        //var_dump($package);
        
        if ($package['type'] !== 'magento-module') {
            $output->writeln('<error>this package is not of type "magento module"</error>');
        } else {
            $packageDir = $composerHelper->getVendorDirectory()->getPathname().'/'.$package['name']; // @todo not secure
            
            $deployStrategy = Factory::getDeployStrategyObject(
                $composerHelper->getMagentoProjectConfig()->getDeployStrategy(),
                $packageDir,
                realpath($composerHelper->getMagentoProjectConfig()->getMagentoRootDir())
            );
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
