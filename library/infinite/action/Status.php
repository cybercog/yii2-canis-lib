<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\action;

use Yii;

/**
 * Status [[@doctodo class_description:infinite\action\Status]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Status extends \infinite\base\Component
{
    const MESSAGE_LEVEL_INFO = '_i';
    const MESSAGE_LEVEL_WARNING = '_w';
    const MESSAGE_LEVEL_ERROR = '_e';

    /**
     * @var [[@doctodo var_type:lastUpdate]] [[@doctodo var_description:lastUpdate]]
     */
    public $lastUpdate;
    /**
     * @var [[@doctodo var_type:linearTasks]] [[@doctodo var_description:linearTasks]]
     */
    public $linearTasks = true;
    /**
     * @var [[@doctodo var_type:cleaned]] [[@doctodo var_description:cleaned]]
     */
    public $cleaned = false;
    /**
     * @var [[@doctodo var_type:paused]] [[@doctodo var_description:paused]]
     */
    public $paused = false;

    /**
     * @var [[@doctodo var_type:_status]] [[@doctodo var_description:_status]]
     */
    protected $_status;
    /**
     * @var [[@doctodo var_type:_log]] [[@doctodo var_description:_log]]
     */
    protected $_log;
    /**
     * @var [[@doctodo var_type:_tasks]] [[@doctodo var_description:_tasks]]
     */
    protected $_tasks;

    /**
     * @var [[@doctodo var_type:_currentTask]] [[@doctodo var_description:_currentTask]]
     */
    protected $_currentTask;

    /**
     * @var [[@doctodo var_type:_messages]] [[@doctodo var_description:_messages]]
     */
    protected $_messages = [];

    /**
     * @var [[@doctodo var_type:_has_error]] [[@doctodo var_description:_has_error]]
     */
    protected $_has_error = false;

    /**
     * @var [[@doctodo var_type:_has_warning]] [[@doctodo var_description:_has_warning]]
     */
    protected $_has_warning = false;

    /**
     * @var [[@doctodo var_type:_commandOutput]] [[@doctodo var_description:_commandOutput]]
     */
    protected $_commandOutput;

    /**
     * @var [[@doctodo var_type:_peak_memory_usage]] [[@doctodo var_description:_peak_memory_usage]]
     */
    protected $_peak_memory_usage;
    /**
     * @var [[@doctodo var_type:_started]] [[@doctodo var_description:_started]]
     */
    protected $_started;
    /**
     * @var [[@doctodo var_type:_ended]] [[@doctodo var_description:_ended]]
     */
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
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
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

    /**
     * [[@doctodo method_description:__wakeup]].
     */
    public function __wakeup()
    {
        if (!empty($this->_tasks)) {
            foreach ($this->_tasks as $task) {
                $task->status = $this;
            }
        }
    }

    /**
     * [[@doctodo method_description:pause]].
     *
     * @return [[@doctodo return_type:pause]] [[@doctodo return_description:pause]]
     */
    public function pause()
    {
        $this->paused = true;
        $this->save();

        return $this;
    }

    /**
     * [[@doctodo method_description:resume]].
     *
     * @return [[@doctodo return_type:resume]] [[@doctodo return_description:resume]]
     */
    public function resume()
    {
        $this->paused = false;
        $this->save();

        return $this;
    }

    /**
     * Get messages.
     *
     * @return [[@doctodo return_type:getMessages]] [[@doctodo return_description:getMessages]]
     */
    public function getMessages()
    {
        return $this->_messages;
    }
    /**
     * [[@doctodo method_description:addMessage]].
     *
     * @param [[@doctodo param_type:message]]      $message      [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]         $data         [[@doctodo param_description:data]] [optional]
     * @param [[@doctodo param_type:messageLevel]] $messageLevel [[@doctodo param_description:messageLevel]] [optional]
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

    /**
     * [[@doctodo method_description:addInfo]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addInfo]] [[@doctodo return_description:addInfo]]
     */
    public function addInfo($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_INFO);
    }

    /**
     * [[@doctodo method_description:addWarning]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addWarning]] [[@doctodo return_description:addWarning]]
     */
    public function addWarning($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_WARNING);
    }

    /**
     * [[@doctodo method_description:addError]].
     *
     * @param [[@doctodo param_type:message]] $message [[@doctodo param_description:message]]
     * @param [[@doctodo param_type:data]]    $data    [[@doctodo param_description:data]] [optional]
     *
     * @return [[@doctodo return_type:addError]] [[@doctodo return_description:addError]]
     */
    public function addError($message, $data = null)
    {
        return $this->addMessage($message, $data, static::MESSAGE_LEVEL_ERROR);
    }

    /**
     * Get progress total weight.
     *
     * @return [[@doctodo return_type:getProgressTotalWeight]] [[@doctodo return_description:getProgressTotalWeight]]
     */
    public function getProgressTotalWeight()
    {
        $n = 0;
        foreach ($this->tasks as $task) {
            $n += $task->weight;
        }

        return $n;
    }

    /**
     * Get progress total.
     *
     * @return [[@doctodo return_type:getProgressTotal]] [[@doctodo return_description:getProgressTotal]]
     */
    public function getProgressTotal()
    {
        return $this->progressTotalWeight;
    }

    /**
     * Get progress remaining.
     *
     * @return [[@doctodo return_type:getProgressRemaining]] [[@doctodo return_description:getProgressRemaining]]
     */
    public function getProgressRemaining()
    {
        return $this->progressTotal - $this->progressDone;
    }

    /**
     * Get progress done.
     *
     * @return [[@doctodo return_type:getProgressDone]] [[@doctodo return_description:getProgressDone]]
     */
    public function getProgressDone()
    {
        $n = [];
        foreach ($this->tasks as $task) {
            if (empty($task->progressTotal)) {
                continue;
            }
            $n[] = ($task->weight * ($task->progressDone / $task->progressTotal));
        }

        return array_sum($n);
    }

    /**
     * Get error.
     *
     * @return [[@doctodo return_type:getHasError]] [[@doctodo return_description:getHasError]]
     */
    public function getHasError()
    {
        return $this->_has_error;
    }

    /**
     * Get has warning.
     *
     * @return [[@doctodo return_type:getHasWarning]] [[@doctodo return_description:getHasWarning]]
     */
    public function getHasWarning()
    {
        return $this->_has_warning;
    }

    /**
     * Get command output.
     *
     * @return [[@doctodo return_type:getCommandOutput]] [[@doctodo return_description:getCommandOutput]]
     */
    public function getCommandOutput()
    {
        return $this->_commandOutput;
    }

    /**
     * Set command output.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:setCommandOutput]] [[@doctodo return_description:setCommandOutput]]
     */
    public function setCommandOutput($value)
    {
        $this->lastUpdate = microtime(true);
        $this->_commandOutput = $value;

        return $this;
    }

    /**
     * Set log.
     *
     * @param [[@doctodo param_type:log]] $log [[@doctodo param_description:log]]
     */
    public function setLog($log)
    {
        $this->_log = $log;
        if (!isset($this->_started)) {
            $this->_started = microtime(true);
        }
    }

    /**
     * Get log.
     *
     * @return [[@doctodo return_type:getLog]] [[@doctodo return_description:getLog]]
     */
    public function getLog()
    {
        return $this->_log;
    }

    /**
     * Get peak memory usage.
     *
     * @return [[@doctodo return_type:getPeakMemoryUsage]] [[@doctodo return_description:getPeakMemoryUsage]]
     */
    public function getPeakMemoryUsage()
    {
        return !isset($this->_peak_memory_usage) ? 0 : $this->_peak_memory_usage;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @param boolean $saveDatabase [[@doctodo param_description:saveDatabase]] [optional]
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
     */
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

    /**
     * Get started.
     *
     * @return [[@doctodo return_type:getStarted]] [[@doctodo return_description:getStarted]]
     */
    public function getStarted()
    {
        return $this->_started;
    }

    /**
     * Get ended.
     *
     * @return [[@doctodo return_type:getEnded]] [[@doctodo return_description:getEnded]]
     */
    public function getEnded()
    {
        return $this->_ended;
    }

    /**
     * Get tasks.
     *
     * @return [[@doctodo return_type:getTasks]] [[@doctodo return_description:getTasks]]
     */
    public function getTasks()
    {
        if (is_null($this->_tasks)) {
            $this->addTask('primary', 'Primary', true);
        }

        return $this->_tasks;
    }

    /**
     * Get task.
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:getTask]] [[@doctodo return_description:getTask]]
     */
    public function getTask($id)
    {
        if (isset($this->tasks[$id])) {
            return $this->tasks[$id];
        }

        return false;
    }

    /**
     * Get current task.
     *
     * @return [[@doctodo return_type:getCurrentTask]] [[@doctodo return_description:getCurrentTask]]
     */
    public function getCurrentTask()
    {
        $tasks = $this->tasks;
        if (isset($tasks[$this->_currentTask])) {
            return $tasks[$this->_currentTask];
        }

        return false;
    }

    /**
     * [[@doctodo method_description:addTask]].
     *
     * @param [[@doctodo param_type:id]]   $id         [[@doctodo param_description:id]]
     * @param [[@doctodo param_type:name]] $name       [[@doctodo param_description:name]]
     * @param boolean                      $setCurrent [[@doctodo param_description:setCurrent]] [optional]
     *
     * @return [[@doctodo return_type:addTask]] [[@doctodo return_description:addTask]]
     */
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
