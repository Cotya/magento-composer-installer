<?php

namespace MagentoHackathon\Composer\Magento\Repository;

use MagentoHackathon\Composer\Magento\InstalledPackage;

/**
 * Interface InstalledPackageRepositoryInterface
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstalledPackageRepositoryInterface
{
    /**
     * Get all installed packages
     *
     * @return InstalledPackage[]
     */
    public function findAll();

    /**
     * @param string $packageName
     * @return InstalledPackage|null
     */
    public function findByPackageName($packageName);

    /**
     * @param InstalledPackage $package
     */
    public function remove(InstalledPackage $package);

    /**
     * @param InstalledPackage $package
     */
    public function add(InstalledPackage $package);

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function has($packageName, $version = null);
}
