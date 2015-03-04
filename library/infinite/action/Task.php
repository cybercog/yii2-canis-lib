<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\action;

use Yii;
use infinite\helpers\Math;
use infinite\helpers\Date;

/**
 * Status [@doctodo write class description for Status].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Task extends \infinite\base\Component
{
    public $name;

    public $saveEvery = 5;
    protected $_weight = 100;
    /**
     * @var __var__status_type__ __var__status_description__
     */
    protected $_status;
    /**
     * @var __var__progressTotal_type__ __var__progressTotal_description__
     */
    protected $_progressTotal = 100;
    /**
     * @var __var__progressRemaining_type__ __var__progressRemaining_description__
     */
    protected $_progressRemaining = 100;

    protected $_started;
    protected $_ended;
    protected $_completed = false;

    protected $_lastStep = false;
    protected $_averageRateChange;
    protected $_stepDurations = [];

    public $parentTask;
    protected $_subtasks = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * Prepares object for serialization.
     *
     * @return __return___sleep_type__ __return___sleep_description__
     */
    public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["\0*\0_log", "\0*\0_registry", "\0*\0_growthRatePartial", "\0*\0_growthRatePartialTop"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }

        return $keys;
    }

    public function __call($name, $params)
    {
        if (isset($this->status) && $this->status->hasMethod($name)) {
            return call_user_func_array([$this->status, $name], $params);
        }

        return parent::__call($name, $params);
    }

    public function start()
    {
        $this->_started = microtime(true);

        return $this;
    }

    public function end()
    {
        if (!$this->_completed) {
            if (!empty($this->tasks)) {
                foreach ($this->tasks as $task) {
                    $task->end();
                }
            }
            $this->_stepDurations = [];
            $this->_completed = true;
            $this->_progressRemaining = 0;
            $this->_ended = microtime(true);
            $this->save();
        }

        return $this;
    }

    public function setStatus($status)
    {
        $this->_status = $status;

        return $this;
    }

    public function getStatus()
    {
        if (isset($this->parentTask)) {
            return $this->parentTask->status;
        }

        return $this->_status;
    }

    public function getProgressTotalWeight()
    {
        $n = 0;
        foreach ($this->tasks as $task) {
            $n += $task->weight;
        }

        return $n;
    }

    /**
     * Set progress total.
     *
     * @param __param_total_type__ $total __param_total_description__
     */
    public function setProgressTotal($total)
    {
        $this->_progressTotal = $total;
        $this->_progressRemaining = $total;

        return $this;
    }

    public function getPercentageDone()
    {
        return round(($this->progressDone / $this->progressTotal) * 100, 2);
    }

    public function getPercentageRemaining()
    {
        return round(($this->progressRemaining / $this->progressTotal) * 100, 2);
    }

    /**
     * Get progress total.
     *
     * @return __return_getProgressTotal_type__ __return_getProgressTotal_description__
     */
    public function getProgressTotal()
    {
        if (!empty($this->tasks)) {
            return $this->progressTotalWeight;
        }

        return $this->_progressTotal;
    }

    /**
     * Get progress done.
     *
     * @return __return_getProgressDone_type__ __return_getProgressDone_description__
     */
    public function getProgressDone()
    {
        if (!empty($this->tasks)) {
            $n = [];
            foreach ($this->tasks as $task) {
                if (empty($task->progressTotal)) {
                    continue;
                }
                $n[] = ($task->weight * ($task->progressDone / $task->progressTotal));
            }

            return array_sum($n);
        }

        if (empty($this->progressTotal)) {
            return;
        }

        return $this->progressTotal - $this->progressRemaining;
    }

    /**
     * Get progress remaining.
     *
     * @return __return_getProgressRemaining_type__ __return_getProgressRemaining_description__
     */
    public function getProgressRemaining()
    {
        if (!empty($this->tasks)) {
            return $this->progressTotal - $this->progressDone;
        }

        if (is_null($this->_progressRemaining)) {
            $this->_progressRemaining = $this->progressTotal;
        }

        return $this->_progressRemaining;
    }

    /**
     * __method_reduceRemaining_description__.
     *
     * @param __param_n_type__ $n __param_n_description__
     *
     * @return __return_reduceRemaining_type__ __return_reduceRemaining_description__
     */
    public function reduceRemaining($n = 1)
    {
        if ($this->_lastStep) {
            $duration = (microtime(true) - $this->_lastStep) / $n;
            $this->_stepDurations[] = $duration;
            if (count($this->_stepDurations) > 200) {
                array_shift($this->_stepDurations);
            }
        }
        $this->_lastStep = microtime(true);
        $this->_progressRemaining = $this->progressRemaining - $n;
        if (empty($this->saveEvery)) {
            $this->saveEvery = 5;
        }
        if ($this->_progressRemaining % $this->saveEvery === 0 || $this->_progressRemaining === 0) {
            $this->save();
        }

        return true;
    }

    public function getRate($limit = null)
    {
        if (!$this->status->linearTasks) {
            return false;
        }
        if (!empty($this->tasks) || count($this->_stepDurations) < max(5, $limit)) {
            return false;
        }
        if (!isset($limit)) {
            $limit = 100;
        }
        $stepDurations = array_slice($this->_stepDurations, -1 * $limit);

        return array_sum($stepDurations) / count($stepDurations);
    }

    public function getRateGrowth($x)
    {
        if (!$this->status->linearTasks) {
            return false;
        }
        if (!empty($this->tasks) || count($this->_stepDurations) < min(100, .1 * $this->progressTotal)) {
            return false;
        }
        if (!isset($this->_averageRateChange)) {
            $rateChanges = [];
            $lastDuration = false;
            foreach ($this->_stepDurations as $key => $duration) {
                if ($lastDuration) {
                    $rateChanges[] = $duration - $lastDuration;
                }
                $lastDuration = $duration;
            }
            $rateChanges = Math::removeOutliers($rateChanges, 4);
            $this->_averageRateChange = array_sum($rateChanges) / count($rateChanges);
            if ($this->_averageRateChange < 0) {
                $this->_averageRateChange = 0;
            }
        }
        //$duration = ($x * $this->rate) + pow($this->_averageRateChange, $x);
        $duration = ($x * $this->getRate(10));
        while ($x > 0 && !empty($this->_averageRateChange)) {
            $duration = $duration + (($this->_averageRateChange * $x)/2);
            $x--;
        }

        return $duration;
    }

    public function getDurationEstimate()
    {
        if (!$this->status->linearTasks) {
            return false;
        }
        if (!empty($this->tasks)) {
            $n = 0;
            foreach ($this->tasks as $task) {
                if (!empty($task->ended)) {
                    continue;
                }
                if (empty($task->started)) {
                    return false;
                }

                $n += $task->durationEstimate;
            }

            return $n;
        }
        if (count($this->_stepDurations) < 30) {
            return false;
        }
        if ($this->progressRemaining <= 0) {
            return 0;
        }

        return $this->progressRemaining * $this->getRate(100);

        return $this->getRateGrowth($this->progressRemaining);
    }

    public function getLog()
    {
        return $this->status->log;
    }

    public function save()
    {
        return $this->status->save();
    }

    public function getStarted()
    {
        if ($this->tasks) {
            $started = [];
            foreach ($this->tasks as $task) {
                if (!empty($task->started)) {
                    $started[] = $task->started;
                }
            }
            if (empty($started)) {
                return;
            }

            return min($started);
        }

        return $this->_started;
    }

    public function getEnded()
    {
        if ($this->tasks) {
            $ended = [];
            foreach ($this->tasks as $task) {
                if (!empty($task->ended)) {
                    $ended[] = $task->ended;
                }
            }
            if (empty($ended) || count($ended) !== count($this->tasks)) {
                return;
            }

            return max($ended);
        }

        return $this->_ended;
    }

    public function getWeight()
    {
        if (!isset($this->_weight) && $this->tasks) {
            $weight = [];
            foreach ($this->tasks as $task) {
                if (!empty($task->weight)) {
                    $weight[] = $task->weight;
                }
            }
            if (!empty($weight)) {
                return array_sum($weight);
            }
        } elseif (!isset($this->_weight)) {
            return 100;
        }

        return $this->_weight;
    }

    public function setWeight($weight)
    {
        $this->_weight = $weight;

        return $this;
    }

    public function addTask($id, $name)
    {
        if (is_null($this->_subtasks)) {
            $this->_subtasks = [];
        }
        $config = ['class' => static::className()];
        $config['name'] = $name;
        $config['parentTask'] = $this;
        $task = $this->_subtasks[$id] = Yii::createObject($config);

        return $task;
    }

    public function getTasks()
    {
        if (is_null($this->_subtasks)) {
            $this->_subtasks = [];
        }

        return $this->_subtasks;
    }

    public function getTask($id)
    {
        if (isset($this->tasks[$id])) {
            return $this->tasks[$id];
        }

        return false;
    }

    public function getPackage()
    {
        $estimate = $this->durationEstimate;
        if ($estimate) {
            $estimate = Date::shortDuration($estimate);
        }
        $rate = $this->rate;
        if ($rate) {
            $rate = Date::shortDuration($rate);
        }
        $task = [
            'name' => $this->name,
            'total' => $this->progressTotal,
            'done' => $this->progressDone,
            'weight' => $this->weight,
            'estimate' => $estimate,
            'rate' => $rate,
        ];

        if (!empty($this->tasks)) {
            $task['subtasks'] = [];
            foreach ($this->tasks as $id => $subtask) {
                $task['subtasks'][$id] = $subtask->package;
            }
        }

        if (isset($this->ended) && !$this->status->linearTasks) {
            $task['duration'] = Date::shortDuration($this->ended - $this->started);
        }

        return $task;
    }
}
