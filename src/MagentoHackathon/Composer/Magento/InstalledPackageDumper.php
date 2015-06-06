<?php

namespace MagentoHackathon\Composer\Magento;

/**
 * Class InstalledPackageDumper
 * @package MagentoHackathon\Composer\Magento
 */
class InstalledPackageDumper
{
    /**
     * @param InstalledPackage $installedPackage
     * @return array
     */
    public function dump(InstalledPackage $installedPackage)
    {
        return array(
            'packageName' => $installedPackage->getName(),
            'version' => $installedPackage->getVersion(),
            'installedFiles' => $installedPackage->getInstalledFiles(),
            'sourceReference' => $installedPackage->getSourceReference(),
        );
    }

    /**
     * @param array $data
     * @return InstalledPackage
     */
    public function restore(array $data)
    {
        $data['sourceReference'] = isset($data['sourceReference']) ? $data['sourceReference'] : null;
        return new InstalledPackage(
            $data['packageName'],
            $data['version'],
            $data['installedFiles'],
            $data['sourceReference']
        );
    }
}
