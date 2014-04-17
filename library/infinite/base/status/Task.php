<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\status;

/**
 * Task [@doctodo write class description for Task]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Task extends \infinite\base\Component
{
    /**
     * @var __var_parent_type__ __var_parent_description__
     */
    public $parent;
    /**
     * @var __var_human_type__ __var_human_description__
     */
    public $human;
    /**
     * @var __var_system_type__ __var_system_description__
     */
    public $system;
    /**
     * @var __var_startTime_type__ __var_startTime_description__
     */
    public $startTime;
    /**
     * @var __var_endTime_type__ __var_endTime_description__
     */
    public $endTime;
    /**
     * @var __var_startMemory_type__ __var_startMemory_description__
     */
    public $startMemory;
    /**
     * @var __var_endMemory_type__ __var_endMemory_description__
     */
    public $endMemory;
    /**
     * @var __var_percentage_type__ __var_percentage_description__
     */
    public $percentage = 0;
    /**
     * @var __var_messages_type__ __var_messages_description__
     */
    public $messages = [];
    /**
     * @var __var_error_type__ __var_error_description__
     */
    public $error = false;

    const MESSAGE_INFO = '__info';
    const MESSAGE_ERROR = '__error';
    const MESSAGE_NOTICE = '__notice';

    /**
    * @inheritdoc
    **/
    public function __construct($parent, $system, $human = null)
    {
        if (is_null($human)) { $human = $system; }
        $this->human = $human;
        $this->system = $system;
        $this->parent = $parent;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * __method_addMessage_description__
     * @param __param_message_type__ $message __param_message_description__
     * @param string $status __param_status_description__ [optional]
     * @return __return_addMessage_type__ __return_addMessage_description__
     */
    public function addMessage($message, $status = self::MESSAGE_INFO)
    {
        $this->messages[] = ['time' => microtime(true), 'status' => $status, 'message' => $message];

        return true;
    }

    /**
     * __method_error_description__
     * @param __param_message_type__ $message __param_message_description__ [optional]
     * @return __return_error_type__ __return_error_description__
     */
    public function error($message = null)
    {
        if (is_null($message)) { $message = true; }
        $this->error = $message;

        return true;
    }
    /**
     * __method_end_description__
     */
    public function end()
    {
        $this->percentage = 100;
        $this->endTime = microtime(true);
        $this->endMemory = memory_get_usage(true);
    }

    /**
     * __method_getMemoryUsage_description__
     * @return __return_getMemoryUsage_type__ __return_getMemoryUsage_description__
     */
    public function getMemoryUsage()
    {
        return $this->endMemory - $this->startMemory;
    }

    /**
     * __method_getDuration_description__
     * @return __return_getDuration_type__ __return_getDuration_description__
     */
    public function getDuration()
    {
        if (is_null($this->endTime)) {
            return round(microtime(true) - $this->startTime, 3);
        } else {
            return round($this->endTime - $this->startTime, 3);
        }
    }
}
