<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class InstallStrategyFactory
 * @package MagentoHackathon\Composer\Magento\Deploystrategy
 */
class InstallStrategyFactory
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var ParserFactoryInterface
     */
    protected $parserFactory;

    /**
     * @param ProjectConfig $config
     * @param ParserFactoryInterface $parserFactory
     */
    public function __construct(ProjectConfig $config, ParserFactoryInterface $parserFactory)
    {
        $this->config = $config;
        $this->parserFactory = $parserFactory;
    }

    /**
     * @param PackageInterface $package
     * @param string $packageSourcePath
     * @return DeploystrategyAbstract
     */
    public function make(PackageInterface $package, $packageSourcePath)
    {
        $strategyName = $this->config->getModuleSpecificDeployStrategy($package->getName());

        $ns = '\MagentoHackathon\Composer\Magento\Deploystrategy\\';
        $className = $ns . ucfirst($strategyName);
        if (!class_exists($className)) {
            $className  = $ns . 'Symlink';
        }

        $strategy = new $className($packageSourcePath, realpath($this->config->getMagentoRootDir()));
        $strategy->setIgnoredMappings($this->config->getModuleSpecificDeployIgnores($package->getName()));
        $strategy->setIsForced($this->config->getMagentoForceByPackageName($package->getName()));

        $mappingParser = $this->parserFactory->make($package, $packageSourcePath);
        $strategy->setMappings($mappingParser->getMappings());

        return $strategy;
    }
}
