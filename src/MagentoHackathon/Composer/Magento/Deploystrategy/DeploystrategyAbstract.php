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
    protected $_mappings = array();

    /**
     * The magento installation's base directory
     *
     * @var string
     */
    protected $_dest_dir;

    /**
     * The module's base directory
     *
     * @var string
     */
    protected $_source_dir;

    /**
     * Constructor
     *
     * @param string $dest_dir
     * @param string $source_dir
     */
    public function __construct($dest_dir, $source_dir)
    {
        $this->_dest_dir = $dest_dir;
        $this->_source_dir = $source_dir;
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
    protected function _getDestDir()
    {
        return $this->_dest_dir;
    }

    /**
     * Returns the current path of the extension
     *
     * @return mixed
     */
    protected function _getSourceDir()
    {
        return $this->_source_dir;
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
        return $this->_mappings;
    }

    /**
     * Sets path mappings to map project's directories to magento's directory structure
     *
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->_mappings = $mappings;
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
