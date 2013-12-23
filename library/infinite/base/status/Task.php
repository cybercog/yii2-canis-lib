<?php
/**
 * library/helpers/Purifier.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base\status;

class Task extends \infinite\base\Component
{
    public $parent;
    public $human;
    public $system;
    public $startTime;
    public $endTime;
    public $startMemory;
    public $endMemory;
    public $percentage = 0;
    public $messages = [];
    public $error = false;

    const MESSAGE_INFO = '__info';
    const MESSAGE_ERROR = '__error';
    const MESSAGE_NOTICE = '__notice';


    public function __construct($parent, $system, $human = null)
    {
        if (is_null($human)) { $human = $system; }
        $this->human = $human;
        $this->system = $system;
        $this->parent = $parent;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function addMessage($message, $status = self::MESSAGE_INFO)
    {
        $this->messages[] = ['time' => microtime(true), 'status' => $status, 'message' => $message];
        return true;
    }

    public function error($message = null)
    {
        if (is_null($message)) { $message = true; }
        $this->error = $message;
        return true;
    }
    public function end()
    {
        $this->percentage = 100;
        $this->endTime = microtime(true);
        $this->endMemory = memory_get_usage(true);
    }

    public function getMemoryUsage()
    {
        return $this->endMemory - $this->startMemory;
    }

    public function getDuration()
    {
        if (is_null($this->endTime)) {
            return round(microtime(true) - $this->startTime, 3);
        } else {
            return round($this->endTime - $this->startTime, 3);
        }
    }
}
