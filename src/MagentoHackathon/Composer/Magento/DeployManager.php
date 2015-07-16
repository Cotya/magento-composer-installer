<?php
/**
 *
 *
 *
 *
 */

namespace MagentoHackathon\Composer\Magento;

use Composer\IO\IOInterface;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Deploystrategy\Copy;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;

class DeployManager
{

    const SORT_PRIORITY_KEY = 'magento-deploy-sort-priority';

    /**
     * @var Entry[]
     */
    protected $packages = array();

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * an array with package names as key and priorities as value
     *
     * @var array
     */
    protected $sortPriority = array();

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param EventManager $eventManager
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param Entry $package
     */
    public function addPackage(Entry $package)
    {
        $this->packages[] = $package;
    }

    /**
     * @param $priorities
     */
    public function setSortPriority($priorities)
    {
        $this->sortPriority = $priorities;
    }

    /**
     * uses the sortPriority Array to sort the packages.
     * Highest priority first.
     * Copy gets per default higher priority then others
     */
    protected function sortPackages()
    {
        $sortPriority = $this->sortPriority;
        $getPriorityValue = function (Entry $object) use ($sortPriority) {
            $result = 100;
            if (isset($sortPriority[$object->getPackageName()])) {
                $result = $sortPriority[$object->getPackageName()];
            } elseif ($object->getDeployStrategy() instanceof Copy) {
                $result = 101;
            }
            return $result;
        };
        usort(
            $this->packages,
            function ($a, $b) use ($getPriorityValue) {
                /** @var Entry $a */
                /** @var Entry $b */
                $aVal = $getPriorityValue($a);
                $bVal = $getPriorityValue($b);
                if ($aVal == $bVal) {
                    return 0;
                }
                return ($aVal > $bVal) ? -1 : 1;
            }
        );
    }

    /**
     * Deploy all the queued packages
     */
    public function doDeploy()
    {
        $this->sortPackages();
        /** @var Entry $package */
        foreach ($this->packages as $package) {
            $this->eventManager->dispatch(new PackageDeployEvent('pre-package-deploy', $package));
            $package->getDeployStrategy()->deploy();
            $this->eventManager->dispatch(new PackageDeployEvent('post-package-deploy', $package));
        }
    }

    /**
     * @return Deploy\Manager\Entry[]
     */
    public function getEntries()
    {
        return $this->packages;
    }
}
