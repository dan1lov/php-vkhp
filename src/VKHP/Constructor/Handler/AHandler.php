<?php
namespace VKHP\Constructor\Handler;

/**
 * Abstract class for Handler
 */
abstract class AHandler
{
    /**
     * @var array
     */
    protected $events;

    /**
     * @see \VKHP\Constructor\Handler::addEventCallback
     */
    public function addEventCallback(string $event, callable $callback)
    {
        if (isset($this->events[$event])) {
            $error_message = "Event '{$event}' already been added, passed"
                . " with the same event was replaced with a new one";
            trigger_error($error_message);
        }

        $this->events[$event] = $callback;
    }
}
