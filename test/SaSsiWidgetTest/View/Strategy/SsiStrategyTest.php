<?php

namespace SaSsiWidgetTest\View\Strategy;

use Mockery,
    Zend\Http\PhpEnvironment\Request,
    Zend\Http\PhpEnvironment\Response,
    Zend\Mvc\MvcEvent,
    Zend\Mvc\Router\RouteMatch,
    Zend\View\ViewEvent;

class SsiStrategyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SaSsiWidget\View\Strategy\SsiStrategy
     */
    protected $strategy;

    /**
     * @var SaSsiWidget\View\Renderer\SsiRenderer
     */
    protected $renderer;

    public function setUp()
    {
        $this->renderer = new \SaSsiWidget\View\Renderer\SsiRenderer;
        $this->strategy = new \SaSsiWidget\View\Strategy\SsiStrategy(
            $this->renderer
        );
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testAttachAttachesEvents()
    {
        $events = Mockery::mock('Zend\EventManager\EventManager');
        $events
            ->shouldReceive('attach')
            ->with(ViewEvent::EVENT_RENDERER, array($this->strategy, 'selectRenderer'), 1)
            ->once();
        $events
            ->shouldReceive('attach')
            ->with(ViewEvent::EVENT_RESPONSE, array($this->strategy, 'injectResponse'), 1)
            ->once();
        $this->strategy->attach($events);
    }

    public function testAttachAttachesEventsWithPriority()
    {
        $events = Mockery::mock('Zend\EventManager\EventManager');
        $events
            ->shouldReceive('attach')
            ->with(
                ViewEvent::EVENT_RENDERER,
                array($this->strategy, 'selectRenderer'),
                100
            )
            ->once();
        $events
            ->shouldReceive('attach')
            ->with(
                ViewEvent::EVENT_RESPONSE,
                array($this->strategy, 'injectResponse'),
                100
            )
            ->once();
        $this->strategy->attach($events, 100);
    }

    public function testAttachAttachesEventsWithNullPriority()
    {
        $events = Mockery::mock('Zend\EventManager\EventManager');
        $events
            ->shouldReceive('attach')
            ->with(
                ViewEvent::EVENT_RENDERER,
                array($this->strategy, 'selectRenderer')
            )
            ->once();
        $events
            ->shouldReceive('attach')
            ->with(
                ViewEvent::EVENT_RESPONSE,
                array($this->strategy, 'injectResponse')
            )
            ->once();
        $this->strategy->attach($events, null);
    }

    public function testDetachDetachesEvents()
    {
        $events = \Mockery::mock('\Zend\EventManager\EventManager[detach]');
        $events->shouldReceive('detach')->andReturn(true)->times(2);

        $this->strategy->attach($events);
        $this->strategy->detach($events);
    }

    public function testSetSurrogateCapability()
    {
        $this->strategy->setSurrogateCapability();

        $surrogateCapability = new \ReflectionProperty(
            'SaSsiWidget\View\Strategy\SsiStrategy',
            'surrogateCapability'
        );
        $surrogateCapability->setAccessible(true);
        $this->assertTrue($surrogateCapability->getValue($this->strategy));
    }

    public function testSelectRendererNotSurrogateCapable()
    {
        $e = new ViewEvent();
        $e->setRequest(new Request());
        $renderer = $this->strategy->selectRenderer($e);
        $this->assertNull($renderer);
    }

    public function testSelectRenderer()
    {
        $e = new ViewEvent();
        $e->setRequest(new Request());
        $this->strategy->setSurrogateCapability();
        $renderer = $this->strategy->selectRenderer($e);
        $this->assertInstanceOf('SaSsiWidget\View\Renderer\SsiRenderer', $renderer);
    }

    public function testInjectResponseUnknownRenderer()
    {
        $e = new ViewEvent();
        $response = new Response();
        $e->setResponse($response);
        $e->setResult('foo');
        $this->strategy->injectResponse($e);
        $this->assertEmpty($response->getContent());
    }

    public function testInjectResponseSsiRenderer()
    {
        $e = new ViewEvent();
        $response = new Response();
        $e->setResponse($response);
        $e->setResult('foo');
        $e->setRenderer($this->renderer);
        $this->strategy->injectResponse($e);
        $this->assertEquals('foo', $response->getContent());
    }
}
