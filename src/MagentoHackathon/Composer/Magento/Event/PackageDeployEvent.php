<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;
use MagentoHackathon\Composer\Magento\Deploy\Manager\Entry;

/**
 * Class PackageDeployEvent
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class PackageDeployEvent extends Event
{
    /**
     * @var Entry
     */
    protected $deployEntry;

    /**
     * @param string $name
     * @param Entry  $deployEntry
     */
    public function __construct($name, Entry $deployEntry)
    {
        parent::__construct($name);
        $this->deployEntry = $deployEntry;
    }

    /**
     * @return Entry
     */
    public function getDeployEntry()
    {
        return $this->deployEntry;
    }
}
