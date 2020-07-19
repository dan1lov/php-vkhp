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

    public function __construct(
        string $id,
        array $aliases,
        callable $command
    ) {
        if (empty($id)) {
            throw new \Exception('id can not be a empty string');
        }
        if (empty($aliases)) {
            throw new \Exception('command should have one or more aliases');
        }

        $this->id = $id;
        $this->aliases = $aliases;
        $this->command = $command;
    }

    public function __get(string $prop)
    {
        return $this->$prop;
    }

    public function __isset(string $prop): bool
    {
        return isset($this->$prop);
    }
}
