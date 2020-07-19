<?php
namespace VKHP\Constructor\Handler;
use VKHP\Constructor\Handler;

/**
 * Callback handler
 */
class Callback implements Handler
{
    /**
     * @var array
     */
    protected $events;

    /**
     * @see Handler::getParameters
     */
    public function getParameters(bool $assoc = false): object
    {
        # TODO: validate data
        $encoded_data = file_get_contents('php://input');
        $decoded_data = json_decode($encoded_data, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('input data is empty or cannot be decoded');
        }

        return $decoded_data;
    }

    /**
     * @see Handler::run
     */
    public function run()
    {
        $parameters = $this->getParameters();
        $event = $parameters->type ?? null;

        if (array_key_exists($event, $this->events)) {
            ($this->events[$event])($parameters->object);
        }

        $this->sendOk();
    }

    /**
     * @see Handler::setEventCallback
     */
    public function setEventCallback(string $event, callable $callback)
    {
        if (isset($this->events[$event])) {
            $error_message = "Event '{$event}' already been added, passed"
                . " with the same event was replaced with a new one";
            trigger_error($error_message, E_USER_NOTICE);
        }

        $this->events[$event] = $callback;
    }

    /**
     * Exit with message 'ok'
     *
     * @return void
     */
    public function sendOk()
    {
        exit('ok');
    }
}
