<?php

namespace MagentoHackathon\Composer\Magento\Repository;

use MagentoHackathon\Composer\Magento\InstalledPackage;
use MagentoHackathon\Composer\Magento\InstalledPackageDumper;

/**
 * Class InstalledPackageFileSystemRepository
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledPackageFileSystemRepository implements InstalledPackageRepositoryInterface
{

    /**
     * @var string Path to state file
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $packages = array();

    /**
     * @var bool Flag to indicate if we have read the existing file
     */
    protected $isLoaded = false;

    /**
     * @var bool Flag to indicate if we need to write once we are finished
     */
    protected $hasChanges = false;

    /**
     * @var InstalledPackageDumper
     */
    protected $dumper;

    /**
     * If file exists, check its readable
     * Check in any case that it's writeable
     *
     * @param string $filePath
     * @param InstalledPackageDumper $dumper
     */
    public function __construct($filePath, InstalledPackageDumper $dumper)
    {
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not writable', $filePath));
        }

        if (file_exists($filePath) && !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not readable', $filePath));
        }

        if (!file_exists($filePath) && !is_writable(dirname($filePath))) {
            throw new \InvalidArgumentException(sprintf('Directory "%s" is not writable', dirname($filePath)));
        }

        $this->filePath = $filePath;
        $this->dumper = $dumper;
    }

    /**
     * @return array
     */
    public function findAll()
    {
        $this->load();
        return $this->packages;
    }

    /**
     * @param string $packageName
     * @return InstalledPackage
     * @throws \Exception
     */
    public function findByPackageName($packageName)
    {
        $this->load();
        foreach ($this->packages as $package) {
            if ($package->getName() === $packageName) {
                return $package;
            }
        }

        throw new \Exception(sprintf('Package Installed Files for: "%s" not found', $packageName));
    }

    /**
     * If version specified, perform a strict check,
     * which only returns true if repository has the package in the specified version
     *
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function has($packageName, $version = null)
    {
        $this->load();
        try {
            $package = $this->findByPackageName($packageName);

            if (null === $version) {
                return true;
            }

            return $package->getVersion() === $version;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param InstalledPackage $package
     * @throws \Exception
     */
    public function add(InstalledPackage $package)
    {
        $this->load();

        try {
            $this->findByPackageName($package->getName());
        } catch (\Exception $e) {
            $this->packages[] = $package;
            $this->hasChanges = true;
            return;
        }

        throw new \Exception(sprintf('Package: "%s" is already installed', $package->getName()));
    }

    /**
     * @param InstalledPackage $package
     * @throws \Exception
     */
    public function remove(InstalledPackage $package)
    {
        $this->load();

        foreach ($this->packages as $key => $installedPackage) {
            if ($installedPackage->getName() === $package->getName()) {
                array_splice($this->packages, $key, 1);
                $this->hasChanges = true;
                return;
            }
        }

        throw new \Exception(sprintf('Package: "%s" not found', $package->getName()));
    }

    /**
     * Load the Mappings File
     *
     * @return array
     */
    private function load()
    {
        if (!$this->isLoaded && file_exists($this->filePath)) {
            $data = json_decode(file_get_contents($this->filePath), true);

            foreach ($data as $installedPackageData) {
                $this->packages[] = $this->dumper->restore($installedPackageData);
            }
        }

        $this->isLoaded = true;
    }

    /**
     * Do the write on destruct, we shouldn't have to do this manually
     * - you don't call save after adding an entry to the database
     * and at the same time, do want to perform IO for each package addition/removal.
     *
     * Also I don't like enforcing the consumer to call save and load.
     */
    public function __destruct()
    {
        if ($this->hasChanges) {
            $data = array();
            foreach ($this->packages as $installedPackage) {
                $data[] = $this->dumper->dump($installedPackage);
            }

            file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
