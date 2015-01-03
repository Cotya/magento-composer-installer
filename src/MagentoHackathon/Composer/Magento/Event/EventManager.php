<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;

/**
 * Class EventManager
 * @package MagentoHackathon\Composer\Magento\Event
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class EventManager
{
    /**
     * @var array
     */
    private $listeners = array();

    /**
     * @param string   $event
     * @param callable $callback
     */
    public function listen($event, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf(
                'Second argument should be a callable. Got: "%s"',
                is_object($callback) ? get_class($callback) : gettype($callback)
            ));
        }

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array($callback);
        } else {
            $this->listeners[$event][] = $callback;
        }
    }

    /**
     * @param Event $event
     */
    public function dispatch(Event $event)
    {
        if (!isset($this->listeners[$event->getName()])) {
            return;
        }

        foreach ($this->listeners[$event->getName()] as $listener) {
            call_user_func_array($listener, array($event));
        }
    }
}
