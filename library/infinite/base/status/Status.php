<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\status;

use yii\base\Event;

/**
 * Status [@doctodo write class description for Status]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Status extends \infinite\base\Component
{
    protected $currentTask;
    protected $levels = 0;
    protected $lastLevel = 0;
    protected $_tasks = [];
    public $maxLevels = 3;

    /**
    * @inheritdoc
    **/
    public function __construct($maxLevels = 3)
    {
        $this->maxLevels = $maxLevels;
    }

    public function get($system)
    {
        if (!isset($this->_tasks[$system])) { $this->startTask($system); }

        return $this->_tasks[$system];
    }

    public function startTask($system, $human = null)
    {
        $this->levels++;
        $this->currentTask = new RTask($this, $system, $human);
        $this->_tasks[$system] = $this->currentTask;
        $this->trigger('startTask');

        return true;
    }

    public function addMessage($message, $messageType = null, $system = null)
    {
        if (!is_null($system) AND isset($this->_tasks[$system])) {
            $this->currentTask = $this->_tasks[$system];
        }

        $task = $this->currentTask;
        if (is_null($task)) { return false; }

        return $task->addMessage($message, $messageType);
    }

    public function updatePercentage($system, $percentage)
    {
        if (!isset($this->_tasks[$system])) { $this->startTask($system); }
        $this->currentTask = $this->_tasks[$system];
        $this->currentTask->percentage = $percentage;
        $this->trigger('updatePercentage');
    }

    public function endTask($system, $error = false, $message = null)
    {
        if (!isset($this->_tasks[$system])) { $this->startTask($system); }
        $this->currentTask = $this->_tasks[$system];
        $this->currentTask->error = $error;
        $this->currentTask->end();
        $this->trigger('endTask', new Event($this, ['message' => $message]));
        $this->levels--;
        $this->lastLevel = $this->levels;
    }
}
