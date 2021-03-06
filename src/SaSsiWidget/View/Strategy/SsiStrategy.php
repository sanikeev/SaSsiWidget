<?php

namespace SaSsiWidget\View\Strategy;

use SaSsiWidget\View\Renderer\SsiRenderer;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\View\ViewEvent;

class SsiStrategy implements ListenerAggregateInterface
{
    /**
     * @var SaSsiWidget\View\Renderer\SsiRenderer
     */
    protected $renderer;

    /**
     * @var bool
     */
    protected $surrogateCapability = false;

    /**
     * Listeners attached to this aggregate
     *
     * @var array
     */
    protected $listeners = array();

    public function __construct(SsiRenderer $ssiRenderer)
    {
        $this->renderer  = $ssiRenderer;
    }

    public function setSurrogateCapability($surrogateCapability = true)
    {
        $this->surrogateCapability = $surrogateCapability;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        if (null === $priority) {
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, array($this, 'selectRenderer'));
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, array($this, 'injectResponse'));
        } else {
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, array($this, 'selectRenderer'), $priority);
            $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, array($this, 'injectResponse'), $priority);
        }
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Selects a renderer by inspecting headers to determine which renderer to use.
     * Only uses headers that have been previously validated
     * Also checks for a "callback" query parameter in Jsonp case
     *
     * @param Zend\View\ViewEvent $e
     */
    public function selectRenderer(ViewEvent $e)
    {
        if ($this->surrogateCapability) {
            return $this->renderer;
        }
    }

    /**
     * Set correct content-type in the response headers
     *
     * @param Zend\View\ViewEvent $e
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            return;
        }

        // Populate response
        $result   = $e->getResult();
        $response = $e->getResponse();
        $response->setContent($result);
    }
}
