<?php
namespace VKHP\Constructor\Handler;
use VKHP\Constructor\Handler;
use VKHP\Constructor\Handler\AHandler;

/**
 * Callback handler
 */
class Callback extends AHandler implements Handler
{
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
     * Exit with message 'ok'
     *
     * @return void
     */
    public function sendOk()
    {
        exit('ok');
    }
}
