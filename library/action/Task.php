<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\action;

use teal\helpers\Date;
use teal\helpers\Math;
use Yii;

/**
 * Task [[@doctodo class_description:teal\action\Task]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Task extends \teal\base\Component
{
    /**
     * @var [[@doctodo var_type:name]] [[@doctodo var_description:name]]
     */
    public $name;

    /**
     * @var [[@doctodo var_type:saveEvery]] [[@doctodo var_description:saveEvery]]
     */
    public $saveEvery = 5;
    /**
     * @var [[@doctodo var_type:_weight]] [[@doctodo var_description:_weight]]
     */
    protected $_weight = 100;
    /**
     * @var [[@doctodo var_type:_status]] [[@doctodo var_description:_status]]
     */
    protected $_status;
    /**
     * @var [[@doctodo var_type:_progressTotal]] [[@doctodo var_description:_progressTotal]]
     */
    protected $_progressTotal = 100;
    /**
     * @var [[@doctodo var_type:_progressRemaining]] [[@doctodo var_description:_progressRemaining]]
     */
    protected $_progressRemaining = 100;

    /**
     * @var [[@doctodo var_type:_started]] [[@doctodo var_description:_started]]
     */
    protected $_started;
    /**
     * @var [[@doctodo var_type:_ended]] [[@doctodo var_description:_ended]]
     */
    protected $_ended;
    /**
     * @var [[@doctodo var_type:_completed]] [[@doctodo var_description:_completed]]
     */
    protected $_completed = false;

    /**
     * @var [[@doctodo var_type:_lastStep]] [[@doctodo var_description:_lastStep]]
     */
    protected $_lastStep = false;
    /**
     * @var [[@doctodo var_type:_averageRateChange]] [[@doctodo var_description:_averageRateChange]]
     */
    protected $_averageRateChange;
    /**
     * @var [[@doctodo var_type:_stepDurations]] [[@doctodo var_description:_stepDurations]]
     */
    protected $_stepDurations = [];

    /**
     * @var [[@doctodo var_type:parentTask]] [[@doctodo var_description:parentTask]]
     */
    public $parentTask;
    /**
     * @var [[@doctodo var_type:_subtasks]] [[@doctodo var_description:_subtasks]]
     */
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
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
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

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if (isset($this->status) && $this->status->hasMethod($name)) {
            return call_user_func_array([$this->status, $name], $params);
        }

        return parent::__call($name, $params);
    }

    /**
     * [[@doctodo method_description:start]].
     *
     * @return [[@doctodo return_type:start]] [[@doctodo return_description:start]]
     */
    public function start()
    {
        $this->_started = microtime(true);

        return $this;
    }

    /**
     * [[@doctodo method_description:end]].
     *
     * @return [[@doctodo return_type:end]] [[@doctodo return_description:end]]
     */
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

    /**
     * Set status.
     *
     * @param [[@doctodo param_type:status]] $status [[@doctodo param_description:status]]
     *
     * @return [[@doctodo return_type:setStatus]] [[@doctodo return_description:setStatus]]
     */
    public function setStatus($status)
    {
        $this->_status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return [[@doctodo return_type:getStatus]] [[@doctodo return_description:getStatus]]
     */
    public function getStatus()
    {
        if (isset($this->parentTask)) {
            return $this->parentTask->status;
        }

        return $this->_status;
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
     * Set progress total.
     *
     * @param [[@doctodo param_type:total]] $total [[@doctodo param_description:total]]
     *
     * @return [[@doctodo return_type:setProgressTotal]] [[@doctodo return_description:setProgressTotal]]
     */
    public function setProgressTotal($total)
    {
        $this->_progressTotal = $total;
        $this->_progressRemaining = $total;

        return $this;
    }

    /**
     * Get percentage done.
     *
     * @return [[@doctodo return_type:getPercentageDone]] [[@doctodo return_description:getPercentageDone]]
     */
    public function getPercentageDone()
    {
        return round(($this->progressDone / $this->progressTotal) * 100, 2);
    }

    /**
     * Get percentage remaining.
     *
     * @return [[@doctodo return_type:getPercentageRemaining]] [[@doctodo return_description:getPercentageRemaining]]
     */
    public function getPercentageRemaining()
    {
        return round(($this->progressRemaining / $this->progressTotal) * 100, 2);
    }

    /**
     * Get progress total.
     *
     * @return [[@doctodo return_type:getProgressTotal]] [[@doctodo return_description:getProgressTotal]]
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
     * @return [[@doctodo return_type:getProgressDone]] [[@doctodo return_description:getProgressDone]]
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
     * @return [[@doctodo return_type:getProgressRemaining]] [[@doctodo return_description:getProgressRemaining]]
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
     * [[@doctodo method_description:reduceRemaining]].
     *
     * @param integer $n [[@doctodo param_description:n]] [optional]
     *
     * @return [[@doctodo return_type:reduceRemaining]] [[@doctodo return_description:reduceRemaining]]
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

    /**
     * Get rate.
     *
     * @param [[@doctodo param_type:limit]] $limit [[@doctodo param_description:limit]] [optional]
     *
     * @return [[@doctodo return_type:getRate]] [[@doctodo return_description:getRate]]
     */
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

    /**
     * Get rate growth.
     *
     * @param [[@doctodo param_type:x]] $x [[@doctodo param_description:x]]
     *
     * @return [[@doctodo return_type:getRateGrowth]] [[@doctodo return_description:getRateGrowth]]
     */
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

    /**
     * Get duration estimate.
     *
     * @return [[@doctodo return_type:getDurationEstimate]] [[@doctodo return_description:getDurationEstimate]]
     */
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

    /**
     * Get log.
     *
     * @return [[@doctodo return_type:getLog]] [[@doctodo return_description:getLog]]
     */
    public function getLog()
    {
        return $this->status->log;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
     */
    public function save()
    {
        return $this->status->save();
    }

    /**
     * Get started.
     *
     * @return [[@doctodo return_type:getStarted]] [[@doctodo return_description:getStarted]]
     */
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

    /**
     * Get ended.
     *
     * @return [[@doctodo return_type:getEnded]] [[@doctodo return_description:getEnded]]
     */
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

    /**
     * Get weight.
     *
     * @return [[@doctodo return_type:getWeight]] [[@doctodo return_description:getWeight]]
     */
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

    /**
     * Set weight.
     *
     * @param [[@doctodo param_type:weight]] $weight [[@doctodo param_description:weight]]
     *
     * @return [[@doctodo return_type:setWeight]] [[@doctodo return_description:setWeight]]
     */
    public function setWeight($weight)
    {
        $this->_weight = $weight;

        return $this;
    }

    /**
     * [[@doctodo method_description:addTask]].
     *
     * @param [[@doctodo param_type:id]]   $id   [[@doctodo param_description:id]]
     * @param [[@doctodo param_type:name]] $name [[@doctodo param_description:name]]
     *
     * @return [[@doctodo return_type:addTask]] [[@doctodo return_description:addTask]]
     */
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

    /**
     * Get tasks.
     *
     * @return [[@doctodo return_type:getTasks]] [[@doctodo return_description:getTasks]]
     */
    public function getTasks()
    {
        if (is_null($this->_subtasks)) {
            $this->_subtasks = [];
        }

        return $this->_subtasks;
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
     * Get package.
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
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
