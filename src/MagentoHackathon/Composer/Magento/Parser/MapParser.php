<?php
/**
 * Composer Magento Installer
 */

namespace MagentoHackathon\Composer\Magento\Parser;

/**
 * Class MapParser
 * @package MagentoHackathon\Composer\Magento\Parser
 */
class MapParser implements Parser
{

    /**
     * @var array
     */
    protected $mappings = array();

    /**
     * @param array $mappings
     */
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }
}
