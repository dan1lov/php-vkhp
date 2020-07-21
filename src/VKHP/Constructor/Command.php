<?php
namespace VKHP\Constructor;

/**
 * Command class for constructor
 */
class Command
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $aliases;

    /**
     * @var callable
     */
    protected $command;

    /**
     * Construct method
     *
     * @param string   $id      Unique string
     * @param array    $aliases Text aliases
     * @param callable $command Callback-function
     */
    public function __construct(
        string $id,
        array $aliases,
        callable $command
    ) {
        if (empty($id)) {
            throw new \Exception('id cannot be empty string');
        }

        $this->id = $id;
        $this->aliases = $aliases;
        $this->command = $command;
    }

    /**
     * Magic method __get
     *
     * @param string $prop prop
     *
     * @return mixed
     */
    public function __get(string $prop)
    {
        return $this->$prop;
    }

    /**
     * Magic method __isset
     *
     * @param string $prop prop
     *
     * @return boolean
     */
    public function __isset(string $prop): bool
    {
        return isset($this->$prop);
    }
}
