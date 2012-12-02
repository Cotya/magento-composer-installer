<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

class MapParser implements Parser {

    protected $_mappings = array();

    function __construct( $mappings )
    {
        $this->setMappings($mappings);
    }

    public function setMappings($mappings)
    {
        $this->_mappings = $mappings;
    }

    public function getMappings()
    {
        return $this->_mappings;
    }

}
