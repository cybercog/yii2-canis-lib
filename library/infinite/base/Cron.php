<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;
use Yii;

class Cron extends Component
{
    const EVENT_HOURLY = '__CRON_HOURLY';
    const EVENT_MORNING = '__CRON_MORNING';
    const EVENT_EVENING = '__CRON_EVENING';
    const EVENT_MIDNIGHT = '__CRON_MIDNIGHT';

    protected static $_instance;

    public static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static;
        }
        return static::$_instance;
    }

    public function getSettings()
    {
        if (!isset($this->_settings)) {
            $this->_settings = [
                'morningHour' => '9',
                'eveningHour' => '18',
                'midnightHour' => '00',
            ];
            if (isset(Yii::$app->params['cron'])) {
                $this->_settings = array_merge($this->_settings, Yii::$app->params['cron']);
            }
        }
        return $this->_settings;
    }


    public function hourly()
    {
        $event = new CronEvent;
        $this->trigger(static::EVENT_HOURLY, $event);
        if ($this->isHour($this->settings['morningHour'])) {
            $this->trigger(static::EVENT_MORNING, $event);
        }
        if ($this->isHour($this->settings['eveningHour'])) {
            $this->trigger(static::EVENT_EVENING, $event);
        }
        if ($this->isHour($this->settings['midnightHour'])) {
            $this->trigger(static::EVENT_MIDNIGHT, $event);
        }
        return $event->isValid;
    }

    public function isHour($hour)
    {
        $currentHour = (int) date("G");
        return $currentHour == $hour;
    }
}
