<?php

namespace MagentoHackathon\Composer\Magento\Repository;

/**
 * Class InstalledFilesFilesystemRepository
 * @package MagentoHackathon\Composer\Magento\Repository
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InstalledFilesFilesystemRepository implements InstalledFilesRepositoryInterface
{

    /**
     * @var string Path to state file
     */
    protected $filePath;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var bool Flag to indicate if we have read the existing file
     */
    protected $isLoaded = false;

    /**
     * @var bool Flag to indicate if we need to write once we are finished
     */
    protected $hasChanges = false;

    /**
     * If file exists, check its readable
     * Check in any case that it's writeable
     *
     * @param string $filePath
     */
    public function __construct($filePath)
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
    }

    /**
     * @param string $packageName
     * @return array
     * @throws \Exception
     */
    public function getByPackage($packageName)
    {
        $this->load();
        if (isset($this->data[$packageName])) {
            return $this->data[$packageName];
        }

        throw new \Exception(sprintf('Package Installed Files for: "%s" not found', $packageName));
    }

    /**
     * @param string $packageName
     * @param array $files
     * @throws \Exception
     */
    public function addByPackage($packageName, array $files)
    {
        $this->load();
        if (isset($this->data[$packageName])) {
            throw new \Exception(sprintf('Package Installed Files for: "%s" are already present', $packageName));
        }

        $this->data[$packageName] = $files;
        $this->hasChanges = true;
    }

    /**
     * @param string $packageName
     * @throws \Exception
     */
    public function removeByPackage($packageName)
    {
        $this->load();
        if (!isset($this->data[$packageName])) {
            throw new \Exception(sprintf('Package Installed Files for: "%s" not found', $packageName));
        }

        unset($this->data[$packageName]);
        $this->hasChanges = true;
    }

    /**
     * Load the Mappings File
     *
     * @return array
     */
    private function load()
    {
        if (!$this->isLoaded && file_exists($this->filePath)) {
            $this->data = json_decode(file_get_contents($this->filePath), true);
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
            file_put_contents($this->filePath, json_encode($this->data));
        }
    }
}
