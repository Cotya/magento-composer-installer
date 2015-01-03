<?php

namespace MagentoHackathon\Composer\Magento\Event;

use Composer\EventDispatcher\Event;

/**
 * Class EventManagerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventManager
     */
    protected $eventManager;

    public function setUp()
    {
        $this->eventManager = new EventManager();
    }

    public function testListenThrowsExceptionIfArgument2NotCallable()
    {
        $this->setExpectedException('InvalidArgumentException', 'Second argument should be a callable. Got: "NULL"');
        $this->eventManager->listen('some-event', null);
    }

    public function testAddListener()
    {
        $this->eventManager->listen('some-event', function () {

        });
        $this->eventManager->listen('some-event', function () {

        });
    }

    public function testDispatchReturnsNullIfNoListenersForEvent()
    {
        $this->assertNull($this->eventManager->dispatch(new Event('no-listeners-for-me')));
    }

    public function testListenerIsCalledForAppropriateEvent()
    {
        $mockCallback = $this->getMock('stdClass', array('callback'));
        $mockCallback->expects($this->exactly(2))
            ->method('callback')
            ->will($this->returnValue(true));

        $this->eventManager->listen('some-event', array($mockCallback, 'callback'));
        $this->eventManager->listen('some-event', array($mockCallback, 'callback'));

        $this->eventManager->dispatch(new Event('some-event'));
    }
}
