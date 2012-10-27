<?php

namespace MagentoHackathon\Composer\Magento\Depolystrategy;

abstract class DeploystrategyAbstract
{

    protected $_mappings = array();

    protected $_dest_dir;

    protected $_source_dir;

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


    public function isForced()
    {
        return 0; // TODO
    }

    public function getMappings()
    {
        return $this->_mappings;
    }

    public function setMappings(Array $mappings)
    {
        $this->_mappings = $mappings;
    }

    abstract public function clean($path);

    abstract public function create($source, $dest);

}

