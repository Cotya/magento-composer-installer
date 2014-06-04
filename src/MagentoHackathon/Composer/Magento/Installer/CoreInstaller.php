<?php
/**
 * CoreInstaller.php
 */

namespace MagentoHackathon\Composer\Magento\Installer;

/**
 * Class CoreInstaller
 *
 * @package MagentoHackathon\Composer\Magento\Installer
 */
class CoreInstaller extends MagentoInstallerAbstract
{
    /**
     * Package Type Definition
     */
    const PACKAGE_TYPE = 'magento-core';

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return self::PACKAGE_TYPE === $packageType;
    }
} 