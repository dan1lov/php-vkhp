<?php
namespace VKHP;

/**
 * Class for manage properties in temporary file
 */
class Scenarios
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $data;

    /**
     * Method for checking existing temporary file
     *
     * @param string  $temp_folder Path to temporary folder
     * @param integer $id          Unique id
     * @param boolean $return      Flag for returning object
     *
     * @return void
     */
    public static function check(string $temp_folder, int $id, bool $return = false)
    {
        if (
            ! file_exists($temp_folder)
            || ! file_exists("{$temp_folder}/file_id{$id}.json")
        ) { return false; }
        elseif (! $return) { return true; }

        return new self($temp_folder, $id);
    }

    public function __construct(string $temp_folder, string $id, array $data = [])
    {
        if (! file_exists($temp_folder)) { return false; }
        $this->id = $id;
        $this->file = "{$temp_folder}/file_id{$id}.json";

        if (file_exists($this->file)) {
            $this->data = json_decode(file_get_contents( $this->file ), true);
            if (isset($this->data['one_time'])) { unlink($this->file); }
        } else $this->data = $data;
    }

    /**
     * Saving data in temporary file
     *
     * @return boolean
     */
    public function save(): bool
    {
        $encoded_data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        $result_of_saving = file_put_contents($this->file, $encoded_data);
        return !is_bool($result_of_saving);
    }

    /**
     * Delete temporary file
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return file_exists($this->file) ? unlink($this->file) : true;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
