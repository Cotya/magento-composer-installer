<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class DeploystrategyFactory
 * @package MagentoHackathon\Composer\Magento\Deploystrategy
 */
class DeploystrategyFactory
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @param ProjectConfig $config
     */
    public function __construct(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param PackageInterface $package
     * @param string $packageSourcePath
     * @return DeploystrategyAbstract
     */
    public function make(PackageInterface $package, $packageSourcePath)
    {
        $strategyName = $this->config->getDeployStrategy();
        if ($this->config->hasDeployStrategyOverwrite()) {
            $moduleSpecificDeployStrategies = $this->config->getDeployStrategyOverwrite();

            if (isset($moduleSpecificDeployStrategies[$package->getName()])) {
                $strategyName = $moduleSpecificDeployStrategies[$package->getName()];
            }
        }

        $ns = '\MagentoHackathon\Composer\Magento\Deploystrategy\\';
        $className = $ns . ucfirst($strategyName);
        if (!class_exists($className)) {
            $className  = $ns . 'Symlink';
        }

        $strategy = new $className($packageSourcePath, realpath($this->config->getMagentoRootDir()));
        $strategy->setIgnoredMappings($this->config->getModuleSpecificDeployIgnores($package->getName()));
        $strategy->setIsForced($this->config->getMagentoForceByPackageName($package->getName()));
        return $strategy;
    }
}
