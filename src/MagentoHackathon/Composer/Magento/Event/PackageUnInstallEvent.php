<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
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
     * @param string           $name
     * @param InstalledPackage $package
     */
    public function __construct($name, InstalledPackage $package)
    {
        parent::__construct($name);
        $this->package = $package;
    }

    /**
     * @return InstalledPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @return array
     */
    public function getInstalledFiles()
    {
        return $this->package->getInstalledFiles();
    }
}
