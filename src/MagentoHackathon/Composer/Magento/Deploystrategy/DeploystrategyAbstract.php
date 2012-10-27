<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Depolystrategy;

/**
 * Abstract deploy strategy
 */
abstract class DeploystrategyAbstract
{
    /**
     * @var array
     */
    protected $_mappings = array();

    /**
     * @var string
     */
    protected $_dest_dir;

    /**
     * @var string
     */
    protected $_source_dir;

    /**
     * @param string $dest_dir
     * @param string $source_dir
     */
    public function __construct($dest_dir, $source_dir)
    {
        $this->_dest_dir = $dest_dir;
        $this->_source_dir = $source_dir;
    }

    public function deploy()
    {
        foreach ($this->getMappings() AS $source => $dest) {
            $this->create($source, $dest);
        }
    }

    /**
     * Get the destination dir of the magento module
     *
     * @return string
     */
    protected function _getDestDir()
    {
        return $this->_dest_dir;
    }

    /**
     * Get the current path of the extension.
     *
     * @return mixed
     */
    protected function _getSourceDir()
    {
        return $this->_source_dir;
    }

    /**
     * @return int
     */
    public function isForced()
    {
        return 0; // TODO
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->_mappings;
    }

    /**
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->_mappings = $mappings;
    }

    /**
     * @param string $path
     * @return void
     */
    abstract public function clean($path);

    /**
     * @param string $source
     * @param string $dest
     * @return void
     */
    abstract public function create($source, $dest);
}
