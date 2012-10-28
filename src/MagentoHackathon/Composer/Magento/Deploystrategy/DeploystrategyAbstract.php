<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Deploystrategy;

/**
 * Abstract deploy strategy
 */
abstract class DeploystrategyAbstract
{
    /**
     * The path mappings to map project's directories to magento's directory structure
     *
     * @var array
     */
    protected $mappings = array();

    /**
     * The magento installation's base directory
     *
     * @var string
     */
    protected $destDir;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $sourceDir;

    /**
     * Constructor
     *
     * @param string $destDir
     * @param string $sourceDir
     */
    public function __construct($destDir, $sourceDir)
    {
        $this->destDir = $destDir;
        $this->sourceDir = $sourceDir;
    }

    /**
     * Executes the deployment strategy for each mapping
     *
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    public function deploy()
    {
        foreach ($this->getMappings() as $data) {
            list ($source, $dest) = $data;
            $this->create($source, $dest);
        }
        return $this;
    }

    /**
     * Returns the destination dir of the magento module
     *
     * @return string
     */
    protected function getDestDir()
    {
        return $this->destDir;
    }

    /**
     * Returns the current path of the extension
     *
     * @return mixed
     */
    protected function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * If set overrides existing files
     *
     * @todo Implement method body
     * @return bool
     */
    public function isForced()
    {
        return false;
    }

    /**
     * Returns the path mappings to map project's directories to magento's directory structure
     *
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * Sets path mappings to map project's directories to magento's directory structure
     *
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * Add a key value pair to mapping
     */
    public function addMapping($key, $value)
    {
        $this->mappings[$key] = $value;
    }

    /**
     * Removes the module's files in the given path
     *
     * @param string $path
     * @return void
     */
    abstract public function clean($path);

    /**
     * Create the module's files in the given destination
     *
     * @param string $source
     * @param string $dest
     * @return \MagentoHackathon\Composer\Magento\Deploystrategy\DeploystrategyAbstract
     */
    abstract public function create($source, $dest);
}
