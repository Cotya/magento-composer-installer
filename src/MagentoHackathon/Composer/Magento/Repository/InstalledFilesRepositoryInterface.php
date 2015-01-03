<?php

namespace MagentoHackathon\Composer\Magento\Repository;

/**
 * Interface InstalledFilesRepositoryInterface
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
interface InstalledFilesRepositoryInterface
{
    /**
     * @param string $packageName
     * @return array
     */
    public function getByPackage($packageName);

    /**
     * @param string $packageName
     */
    public function removeByPackage($packageName);

    /**
     * @param string $packageName
     * @param array $files
     * @return
     */
    public function addByPackage($packageName, array $files);
}
