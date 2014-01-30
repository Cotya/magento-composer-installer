<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento;

class MapParser extends PathTranslationParser {

    protected $_mappings = array();

    function __construct( $mappings, $translations = array() )
    {
        parent::__construct($translations);

        $this->setMappings($mappings);
    }

    public function setMappings($mappings)
    {
        $this->_mappings = $this->translatePathMappings($mappings);
    }

    public function getMappings()
    {
        return $this->_mappings;
    }

}
