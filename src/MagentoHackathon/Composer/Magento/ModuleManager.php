<?php

namespace MagentoHackathon\Composer\Magento;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;
use MagentoHackathon\Composer\Magento\Event\EventManager;
use MagentoHackathon\Composer\Magento\Event\PackageDeployEvent;
use MagentoHackathon\Composer\Magento\Event\PackageUnInstallEvent;
use MagentoHackathon\Composer\Magento\Factory\InstallStrategyFactory;
use MagentoHackathon\Composer\Magento\Repository\InstalledPackageRepositoryInterface;
use MagentoHackathon\Composer\Magento\UnInstallStrategy\UnInstallStrategyInterface;

/**
 * Class ModuleManager
 * @package MagentoHackathon\Composer\Magento
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ModuleManager
{
    /**
     * @var InstalledPackageRepositoryInterface
     */
    protected $installedPackageRepository;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var ProjectConfig
     */
    protected $config;

    /**
     * @var UnInstallStrategyInterface
     */
    protected $unInstallStrategy;

    /**
     * @var InstallStrategyFactory
     */
    protected $installStrategyFactory;

    /**
     * @param InstalledPackageRepositoryInterface $installedRepository
     * @param EventManager $eventManager
     * @param ProjectConfig $config
     * @param UnInstallStrategyInterface $unInstallStrategy
     * @param InstallStrategyFactory $installStrategyFactory
     */
    public function __construct(
        InstalledPackageRepositoryInterface $installedRepository,
        EventManager $eventManager,
        ProjectConfig $config,
        UnInstallStrategyInterface $unInstallStrategy,
        InstallStrategyFactory $installStrategyFactory
    ) {
        $this->installedPackageRepository = $installedRepository;
        $this->eventManager = $eventManager;
        $this->config = $config;
        $this->unInstallStrategy = $unInstallStrategy;
        $this->installStrategyFactory = $installStrategyFactory;
    }

    /**
     * @param array $currentComposerInstalledPackages
     * @return array
     */
    public function updateInstalledPackages(array $currentComposerInstalledPackages)
    {
        $packagesToRemove = $this->getRemoves(
            $currentComposerInstalledPackages,
            $this->installedPackageRepository->findAll()
        );

        $packagesToInstall  = $this->getInstalls($currentComposerInstalledPackages);

        $this->doRemoves($packagesToRemove);
        $this->doInstalls($packagesToInstall);

        return array(
            $packagesToRemove,
            $packagesToInstall
        );
    }

    /**
     * @param PackageInterface[] $packagesToInstall
     */
    public function doInstalls(array $packagesToInstall)
    {
        foreach ($packagesToInstall as $install) {
            $installStrategy = $this->installStrategyFactory->make(
                $install,
                $this->getPackageSourceDirectory($install)
            );

            $deployEntry = new Entry();
            $deployEntry->setPackageName($install->getPrettyName());
            $deployEntry->setDeployStrategy($installStrategy);
            $this->eventManager->dispatch(
                new PackageDeployEvent('pre-package-deploy', $deployEntry)
            );
            $files = $installStrategy->deploy()->getDeployedFiles();
            $this->eventManager->dispatch(
                new PackageDeployEvent('post-package-deploy', $deployEntry)
            );
            $this->installedPackageRepository->add(new InstalledPackage(
                $install->getName(),
                $this->createVersion($install),
                $files
            ));
        }
    }

    /**
     * @param InstalledPackage[] $packagesToRemove
     */
    public function doRemoves(array $packagesToRemove)
    {
        foreach ($packagesToRemove as $remove) {
            $this->eventManager->dispatch(new PackageUnInstallEvent('pre-package-uninstall', $remove));
            $this->unInstallStrategy->unInstall($remove->getInstalledFiles());
            $this->eventManager->dispatch(new PackageUnInstallEvent('post-package-uninstall', $remove));
            $this->installedPackageRepository->remove($remove);
        }
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @param InstalledPackage[] $magentoInstalledPackages
     * @return InstalledPackage[]
     */
    public function getRemoves(array $currentComposerInstalledPackages, array $magentoInstalledPackages)
    {
        //make the package names as the array keys
        if (count($currentComposerInstalledPackages)) {
            $currentComposerInstalledPackages = array_combine(
                array_map(
                    function (PackageInterface $package) {
                        return $package->getName();
                    },
                    $currentComposerInstalledPackages
                ),
                $currentComposerInstalledPackages
            );
        }
        return array_filter(
            $magentoInstalledPackages,
            function (InstalledPackage $package) use ($currentComposerInstalledPackages) {
                if (!isset($currentComposerInstalledPackages[$package->getName()])) {
                    return true;
                }

                $composerPackage = $currentComposerInstalledPackages[$package->getName()];
                return $package->getVersion() !== $this->createVersion($composerPackage);
            }
        );
    }

    /**
     * @param PackageInterface[] $currentComposerInstalledPackages
     * @return PackageInterface[]
     */
    public function getInstalls(array $currentComposerInstalledPackages)
    {
        $repo = $this->installedPackageRepository;
        $packages = array_filter($currentComposerInstalledPackages, function (PackageInterface $package) use ($repo) {
            return !$repo->has($package->getName(), $this->createVersion($package));
        });

        $config = $this->config;
        usort($packages, function (PackageInterface $aObject, PackageInterface $bObject) use ($config) {
            $a = $config->getModuleSpecificSortValue($aObject->getName());
            $b = $config->getModuleSpecificSortValue($bObject->getName());
            if ($a == $b) {
                return strcmp($aObject->getName(), $bObject->getName());
                /**
                 * still changes sort order and breaks a test, so for now strcmp as workaround
                 * to keep the test working.
                 */
                // return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return $packages;
    }

    /**
     * @param PackageInterface $package
     * @return string
     */
    private function getPackageSourceDirectory(PackageInterface $package)
    {
        if ($package instanceof RootPackage) {
            $path = sprintf("%s/..", $this->config->getVendorDir());
        } else {
            $path = sprintf("%s/%s", $this->config->getVendorDir(), $package->getPrettyName());
        }

        $targetDir = $package->getTargetDir();

        if ($targetDir) {
            $path = sprintf("%s/%s", $path, $targetDir);
        }

        $path = realpath($path);
        return $path;
    }

    /**
     * Create a version string which is unique. dev-master
     * packages report a version of 9999999-dev. We need a unique version
     * so we can detect changes. here we use the source reference which
     * in the case of git is the commit hash
     *
     * @param PackageInterface $package
     *
     * @return string
     */
    private function createVersion(PackageInterface $package)
    {
        $version = $package->getVersion();

        if (null !== $package->getSourceReference()) {
            $version = sprintf('%s-%s', $version, $package->getSourceReference());
        }

        return $version;
    }
}
