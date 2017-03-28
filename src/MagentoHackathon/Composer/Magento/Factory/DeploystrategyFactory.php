<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;
use MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract;

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
     * @var array
     */
    protected static $strategies = array(
        'copy'            => '\MagentoHackathon\Composer\Magento\Deploystrategy\Copy',
        'symlink'         => '\MagentoHackathon\Composer\Magento\Deploystrategy\Symlink',
        'absoluteSymlink' => '\MagentoHackathon\Composer\Magento\Deploystrategy\AbsoluteSymlink',
        'link'            => '\MagentoHackathon\Composer\Magento\Deploystrategy\Link',
        'none'            => '\MagentoHackathon\Composer\Magento\Deploystrategy\None',
    );

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

        if (!isset(static::$strategies[$strategyName])) {
            $className = static::$strategies['symlink'];
        } else {
            $className = static::$strategies[$strategyName];
        }

        $strategy = new $className($packageSourcePath, realpath($this->config->getMagentoRootDir()));
        $strategy->setIgnoredMappings($this->config->getModuleSpecificDeployIgnores($package->getName()));
        $strategy->setIsForced($this->config->getMagentoForceByPackageName($package->getName()));
        return $strategy;
    }
}
