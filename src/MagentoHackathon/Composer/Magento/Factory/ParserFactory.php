<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Parser\MapParser;
use MagentoHackathon\Composer\Magento\Parser\ModmanParser;
use MagentoHackathon\Composer\Magento\Parser\PackageXmlParser;
use MagentoHackathon\Composer\Magento\Parser\Parser;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class ParserFactory
 * @package MagentoHackathon\Composer\Magento\Factory
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ParserFactory implements ParserFactoryInterface
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     */
    public function __construct(ProjectConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param PackageInterface $package
     * @param string $sourceDir
     * @return Parser
     * @throws \ErrorException
     */
    public function make(PackageInterface $package, $sourceDir)
    {
        $moduleSpecificMap = $this->config->getMagentoMapOverwrite();
        if (isset($moduleSpecificMap[$package->getName()])) {
            $map = $moduleSpecificMap[$package->getName()];
            return new MapParser($map);
        }

        $extra = $package->getExtra();
        if (isset($extra['map'])) {
            return new MapParser($extra['map']);
        }

        if (isset($extra['package-xml'])) {
            return new PackageXmlParser(sprintf('%s/%s', $sourceDir, $extra['package-xml']));
        }

        $modmanFile = sprintf('%s/modman', $sourceDir);
        if (file_exists($modmanFile)) {
            return new ModmanParser($modmanFile);
        }

        throw new \ErrorException(
            sprintf(
                'Unable to find deploy strategy for module: "%s" no known mapping'.PHP_EOL
                .'sourceDir: "%s"',
                $package->getName(),
                $sourceDir
            )
        );
    }
}
