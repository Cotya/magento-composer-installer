<?php

/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Downloader\VcsDownloader;

/**
 * @author Tiago Ribeiro <tiago.ribeiro@seegno.com>
 * @author Rui Marinho <rui.marinho@seegno.com>
 */
class DeployCommand extends \Composer\Command\Command
{
    protected function configure()
    {
        $this
            ->setName('magento-module-deploy')
            ->setDescription('Deploy all Magento modules loaded via composer.json')
            ->setDefinition(array(
            // we dont need to define verbose, because composer already defined it internal
            //new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Show modified files for each directory that contains changes.'),
        ))
            ->setHelp(<<<EOT
This command deploys all magento Modules

EOT
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // init repos
        $composer = $this->getComposer();
        $installedRepo = $composer->getRepositoryManager()->getLocalRepository();

        $dm = $composer->getDownloadManager();
        $im = $composer->getInstallationManager();

        /*
         * @var $moduleInstaller MagentoHackathon\Composer\Magento\Installer
         */
        $moduleInstaller = $im->getInstaller("magento-module");

        foreach ($installedRepo->getPackages() as $package) {

            if ($input->getOption('verbose')) {
                $output->writeln( $package->getName() );
                $output->writeln( $package->getType() );
            }

            if( $package->getType() != "magento-module" ){
                continue;
            }
            if ($input->getOption('verbose')) {
                $output->writeln("package {$package->getName()} recognized");
            }

            $strategy = $moduleInstaller->getDeployStrategy($package);
            if ($input->getOption('verbose')) {
                $output->writeln("used " . get_class($strategy) . " as deploy strategy");
            }
            $strategy->setMappings($moduleInstaller->getParser($package)->getMappings());

            $strategy->deploy();
        }


        return;
    }
}
