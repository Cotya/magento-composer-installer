<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\InstalledPackage;

/**
 * Class PackageUnInstallEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageUnInstallEvent extends Event
{
    /**
     * @var InstalledPackage
     */
    protected $package;
    /**
     * @var PackageInterface
     */
    protected $composerPackage;

    /**
     * @param string           $name
     * @param InstalledPackage $package
     * @param PackageInterface $composerPackage
     */
    public function __construct($name, InstalledPackage $package, PackageInterface $composerPackage)
    {
        parent::__construct($name);
        $this->package = $package;
        $this->composerPackage = $composerPackage;
    }

    /**
     * @return InstalledPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @return PackageInterface
     */
    public function getComposerPackage()
    {
        return $this->composerPackage;
    }
}
