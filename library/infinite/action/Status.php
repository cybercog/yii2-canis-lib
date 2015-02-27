<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\action;

use Yii;

/**
 * Status [@doctodo write class description for Status]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Status extends \infinite\base\Component
{
    const MESSAGE_LEVEL_INFO = '_i';
    const MESSAGE_LEVEL_WARNING = '_w';
    const MESSAGE_LEVEL_ERROR = '_e';

    public $lastUpdate;
    public $linearTasks = true;
    public $cleaned = false;
    public $paused = false;

    /**
     * @var __var__status_type__ __var__status_description__
     */
    protected $_status;
    /**
     * @var __var__log_type__ __var__log_description__
     */
    protected $_log;
    /**
     * @var __var__settings_type__ __var__settings_description__
     */
    protected $_tasks;

    protected $_currentTask;

    protected $_messages = [];

    protected $_has_error = false;

    protected $_has_warning = false;

    protected $_commandOutput;

    protected $_peak_memory_usage;
    protected $_started;
    protected $_ended;

    /**
    * @inheritdoc
     */
    public function __construct($log = null)
    {
        parent::__construct();
        if (!empty($log)) {
            $this->log = $log;
        }
    }

    /**
     * Prepares object for serialization.
     * @return __return___sleep_type__ __return___sleep_description__
     */
    public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["\0*\0_log", "\0*\0_registry"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }

        return $keys;
    }

    public function __wakeup()
    {
        if (!empty($this->_tasks)) {
            foreach ($this->_tasks as $task) {
                $task->status = $this;
            }
        }
    }

    public function pause()
    {
        $this->paused = true;
        $this->save();
        return $this;
    }
    
    public function resume()
    {
        $this->paused = false;
        $this->save();
        return $this;
    }

    public function getMessages()
    {
        return $this->_messages;
    }
    /**
     * __method_addError_description__
     * @param __param_message_type__ $message __param_message_description__
     */
    public function addMessage($message, $data = null, $messageLevel = null)
    {
        if (is_null($messageLevel)) {
            $messageLevel = static::MESSAGE_LEVEL_INFO;
        }
        if ($messageLevel === static::MESSAGE_LEVEL_ERROR) {
            $this->_has_error = true;
        }
        if ($messageLevel === static::MESSAGE_LEVEL_WARNING) {
            $this->_has_warning = true;
        }
        $this->_messages[] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(),
            'message' => $message,
            'level' => $messageLevel,
            'data' => $data,
        ];
        $this->save();
    }

    public function addInfo($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_INFO);
    }

    public function addWarning($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_WARNING);
    }

    public function addError($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_ERROR);
    }

    public function getProgressTotalWeight()
    {
        $n = 0;
        foreach ($this->tasks as $task) {
            $n += $task->weight;
        }

        return $n;
    }

    public function getProgressTotal()
    {
        return $this->progressTotalWeight;
    }

    public function getProgressRemaining()
    {
        return $this->progressTotal - $this->progressDone;
    }

    public function getProgressDone()
    {
        $n = [];
        foreach ($this->tasks as $task) {
          if (empty($task->progressTotal)) { continue; }
            $n[] = ($task->weight * ($task->progressDone / $task->progressTotal));
        }

        return array_sum($n);
    }

    /**
     * Get error
     * @return __return_getError_type__ __return_getError_description__
     */
    public function getHasError()
    {
        return $this->_has_error;
    }

    public function getHasWarning()
    {
        return $this->_has_warning;
    }

    public function getCommandOutput()
    {
        return $this->_commandOutput;
    }

    public function setCommandOutput($value)
    {
        $this->lastUpdate = microtime(true);

        return $this->_commandOutput = $value;
    }

    public function setLog($log)
    {
        $this->_log = $log;
        if (!isset($this->_started)) {
            $this->_started = microtime(true);
        }
    }

    public function getLog()
    {
        return $this->_log;
    }

    public function getPeakMemoryUsage()
    {
        return !isset($this->_peak_memory_usage) ? 0 : $this->_peak_memory_usage;
    }

    public function save($saveDatabase = false)
    {
        $peakMemoryUsage = memory_get_peak_usage();
        if (is_null($this->_peak_memory_usage) || $this->_peak_memory_usage < $peakMemoryUsage) {
            $this->_peak_memory_usage = $peakMemoryUsage;
        }
        if ($saveDatabase) {
            return $this->_log->saveCache() && $this->_log->save();
        }

        return $this->_log->saveCache();
    }

    public function getStarted()
    {
        return $this->_started;
    }

    public function getEnded()
    {
        return $this->_ended;
    }

    public function getTasks()
    {
        if (is_null($this->_tasks)) {
            $this->addTask('primary', 'Primary', true);
        }

        return $this->_tasks;
    }

    public function getTask($id)
    {
        if (isset($this->tasks[$id])) {
            return $this->tasks[$id];
        }

        return false;
    }

    public function getCurrentTask()
    {
        $tasks = $this->tasks;
        if (isset($tasks[$this->_currentTask])) {
            return $tasks[$this->_currentTask];
        }

        return false;
    }

    public function addTask($id, $name, $setCurrent = false)
    {
        if (is_null($this->_tasks)) {
            $setCurrent = true;
            $this->_tasks = [];
        }
        $config = ['class' => Task::className()];
        $config['name'] = $name;
        $config['status'] = $this;
        if ($setCurrent) {
            $this->_currentTask = $id;
        }
        $task = $this->_tasks[$id] = Yii::createObject($config);

        return $task;
    }
}
