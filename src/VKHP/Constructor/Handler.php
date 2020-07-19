<?php
namespace VKHP\Constructor;

/**
 * Handler interface for callback/longpoll
 */
interface Handler
{
    /**
     * Get parameters
     *
     * @return object
     */
    public function getParameters(): object;

    /**
     * Run
     *
     * @return void
     */
    public function run();

    /**
     * Add callback-function for event
     *
     * @param string $event      Event type
     * @param callable $callback Callback-function
     *
     * @return void
     */
    public function addEventCallback(string $event, callable $callback);
}
