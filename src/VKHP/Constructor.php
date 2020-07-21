<?php
namespace VKHP;
use VKHP\Constructor\Command;

/**
 * Constructor class
 */
class Constructor
{
    /**
     * @var array
     */
    protected $commands;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var null|string
     */
    protected $defaultCommand = null;

    /**
     * Add command to command list
     *
     * @param Command $command Command-object
     *
     * @return void
     */
    public function addCommand(Command $command)
    {
        if (isset($this->commands[$command->id])) {
            $error_message = "Command '{$command->id}' already been added, passed"
                . " with the same id was replaced with a new one";
            trigger_error($error_message);
        }

        $this->commands[$command->id] = $command;
    }

    /**
     * Set parameters for successful executing with execute() method
     *
     * @param object $params Parameters
     *
     * @return void
     */
    public function setParameters(object $params)
    {
        # TODO: validate this parameters
        $this->parameters = $params;
    }

    /**
     * Setting the default command.
     * 
     * Needed in cases where the command was not found, and it is necessary
     * to return the answer in any case
     *
     * @param string $command_id Command id
     *
     * @return boolean
     */
    public function setDefaultCommand(string $command_id): bool
    {
        if (! isset($this->commands[$command_id])) {
            $error_message = "Command '{$command_id}' was not found in the"
                . " command list, it is impossible to set it as default";
            trigger_error($error_message);
            return false;
        }

        $this->defaultCommand = $command_id;
        return true;
    }

    /**
     * Find command, execute, and return the answer
     *
     * @return object
     */
    public function execute(): object
    {
        if (empty($this->parameters)) {
            $error_message = 'parameters not specified, it is impossible'
                . ' to start execution';
            throw new \Eception($error_message);
        }

        $message_obj = $this->parameters->message ?? $this->parameters;
        $payload_c = $message_obj->payload->command ?? null;
        $text = $message_obj->text ?? null;

        $command_id = $this->findCommandId($payload_c, $text);
        if ($command_id !== null || $this->defaultCommand !== null) {
            $callable = $this->commands[$command_id ?? $this->defaultCommand]->command;
            $response = $callable($this->parameters);
        } else {
            $error_message = 'no command was found, nor was the default command set';
            throw new \Exception($error_message);
        }

        return (object) [
            'message' => $response[0] ?? 'null',
            'keyboard' => $response[1] ?? null,
            'attachment' => $response[2] ?? null
        ];
    }

    /**
     * Find command by payload command or text aliases
     *
     * @param string|null $payload_c Payload-command
     * @param string|null $text      Command alias
     *
     * @return null|string
     */
    private function findCommandId(?string $payload_c, ?string $text)
    {
        if (empty($this->commands)) {
            trigger_error('Commands list is empty, nothing to find');
            return null;
        }

        $command_id = null;

        if ($payload_c) {
            $command_id = array_search(
                $payload_c,
                array_column($this->commands, 'id', 'id')
            );
        }

        if ($text && $command_id === null) {
            $commands_aliases = array_column($this->commands, 'aliases', 'id');
            foreach ($commands_aliases as $key => $aliases) {
                if (array_search($text, $aliases) !== false) {
                    $command_id = $key;
                    break;
                }
            }
        }

        return $command_id;
    }
}
