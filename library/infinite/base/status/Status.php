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
    /**
     * @var __var_currentTask_type__ __var_currentTask_description__
     */
    protected $currentTask;
    /**
     * @var __var_levels_type__ __var_levels_description__
     */
    protected $levels = 0;
    /**
     * @var __var_lastLevel_type__ __var_lastLevel_description__
     */
    protected $lastLevel = 0;
    /**
     * @var __var__tasks_type__ __var__tasks_description__
     */
    protected $_tasks = [];
    /**
     * @var __var_maxLevels_type__ __var_maxLevels_description__
     */
    public $maxLevels = 3;

    /**
    * @inheritdoc
    **/
    public function __construct($maxLevels = 3)
    {
        $this->maxLevels = $maxLevels;
    }

    /**
     * Get
     * @param __param_system_type__ $system __param_system_description__
     * @return __return_get_type__ __return_get_description__
     */
    public function get($system)
    {
        if (!isset($this->_tasks[$system])) { $this->startTask($system); }

        return $this->_tasks[$system];
    }

    /**
     * __method_startTask_description__
     * @param __param_system_type__ $system __param_system_description__
     * @param __param_human_type__ $human __param_human_description__ [optional]
     * @return __return_startTask_type__ __return_startTask_description__
     */
    public function startTask($system, $human = null)
    {
        $this->levels++;
        $this->currentTask = new RTask($this, $system, $human);
        $this->_tasks[$system] = $this->currentTask;
        $this->trigger('startTask');

        return true;
    }

    /**
     * __method_addMessage_description__
     * @param __param_message_type__ $message __param_message_description__
     * @param __param_messageType_type__ $messageType __param_messageType_description__ [optional]
     * @param __param_system_type__ $system __param_system_description__ [optional]
     * @return __return_addMessage_type__ __return_addMessage_description__
     */
    public function addMessage($message, $messageType = null, $system = null)
    {
        if (!is_null($system) AND isset($this->_tasks[$system])) {
            $this->currentTask = $this->_tasks[$system];
        }

        $task = $this->currentTask;
        if (is_null($task)) { return false; }

        return $task->addMessage($message, $messageType);
    }

    /**
     * __method_updatePercentage_description__
     * @param __param_system_type__ $system __param_system_description__
     * @param __param_percentage_type__ $percentage __param_percentage_description__
     */
    public function updatePercentage($system, $percentage)
    {
        if (!isset($this->_tasks[$system])) { $this->startTask($system); }
        $this->currentTask = $this->_tasks[$system];
        $this->currentTask->percentage = $percentage;
        $this->trigger('updatePercentage');
    }

    /**
     * __method_endTask_description__
     * @param __param_system_type__ $system __param_system_description__
     * @param boolean $error __param_error_description__ [optional]
     * @param __param_message_type__ $message __param_message_description__ [optional]
     */
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
