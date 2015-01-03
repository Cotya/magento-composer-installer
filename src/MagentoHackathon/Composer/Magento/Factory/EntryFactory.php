<?php

namespace MagentoHackathon\Composer\Magento\Factory;

use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Factory\ParserFactoryInterface;
use MagentoHackathon\Composer\Magento\ProjectConfig;

/**
 * Class EntryFactory
 * @package MagentoHackathon\Composer\Magento\Deploy\Manager
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class EntryFactory
{

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var DeploystrategyFactory
     */
    protected $deploystrategyFactory;

    /**
     * @var ParserFactoryInterface
     */
    protected $parserFactory;

    /**
     * @param ProjectConfig $config
     * @param DeploystrategyFactory $deploystrategyFactory
     * @param ParserFactoryInterface $parserFactory
     */
    public function __construct(
        ProjectConfig $config,
        DeploystrategyFactory $deploystrategyFactory,
        ParserFactoryInterface $parserFactory
    ) {
        $this->config                   = $config;
        $this->deploystrategyFactory    = $deploystrategyFactory;
        $this->parserFactory            = $parserFactory;
    }

    /**
     * @param PackageInterface $package
     * @param string $packageSourceDirectory
     * @return Entry
     */
    public function make(PackageInterface $package, $packageSourceDirectory)
    {
        $entry = new Entry();
        $entry->setPackageName($package->getName());

        $strategy       = $this->deploystrategyFactory->make($package, $packageSourceDirectory);
        $mappingParser  = $this->parserFactory->make($package, $packageSourceDirectory);

        $strategy->setMappings($mappingParser->getMappings());
        $entry->setDeployStrategy($strategy);

        return $entry;
    }
}
