<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use Yii;

/**
 * Cron [[@doctodo class_description:teal\base\Cron]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Cron extends Component
{
    const EVENT_HOURLY = '__CRON_HOURLY';
    const EVENT_MORNING = '__CRON_MORNING';
    const EVENT_EVENING = '__CRON_EVENING';
    const EVENT_MIDNIGHT = '__CRON_MIDNIGHT';
    const EVENT_WEEKLY = '__CRON_WEEKLY';
    const EVENT_MONTHLY = '__CRON_MONTHLY';

    /**
     * @var [[@doctodo var_type:_instance]] [[@doctodo var_description:_instance]]
     */
    protected static $_instance;

    /**
     * Get instance.
     *
     * @return [[@doctodo return_type:getInstance]] [[@doctodo return_description:getInstance]]
     */
    public static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * Get settings.
     *
     * @return [[@doctodo return_type:getSettings]] [[@doctodo return_description:getSettings]]
     */
    public function getSettings()
    {
        if (!isset($this->_settings)) {
            $this->_settings = [
                'morningHour' => '9',
                'eveningHour' => '18',
                'midnightHour' => '00',
                'weeklyDay' => '0',
                'monthlyDay' => '1',
            ];
            if (isset(Yii::$app->params['cron'])) {
                $this->_settings = array_merge($this->_settings, Yii::$app->params['cron']);
            }
        }

        return $this->_settings;
    }

    /**
     * [[@doctodo method_description:hourly]].
     *
     * @return [[@doctodo return_type:hourly]] [[@doctodo return_description:hourly]]
     */
    public function hourly()
    {
        $event = new CronEvent();
        $this->trigger(static::EVENT_HOURLY, $event);
        if ($this->isHour($this->settings['morningHour'])) {
            $this->trigger(static::EVENT_MORNING, $event);
        }
        if ($this->isHour($this->settings['eveningHour'])) {
            $this->trigger(static::EVENT_EVENING, $event);
        }
        if ($this->isHour($this->settings['midnightHour'])) {
            $this->trigger(static::EVENT_MIDNIGHT, $event);
            if ($this->isDayOfWeek($this->settings['weeklyDay'])) {
                $this->trigger(static::EVENT_WEEKLY, $event);
            }
            if ($this->isDayOfMonth($this->settings['monthlyDay'])) {
                $this->trigger(static::EVENT_MONTHLY, $event);
            }
        }

        return $event->isValid;
    }

    /**
     * [[@doctodo method_description:isHour]].
     *
     * @param [[@doctodo param_type:hour]] $hour [[@doctodo param_description:hour]]
     *
     * @return [[@doctodo return_type:isHour]] [[@doctodo return_description:isHour]]
     */
    public function isHour($hour)
    {
        $currentHour = (int) date("G");

        return $currentHour == $hour;
    }
    /**
     * [[@doctodo method_description:isDayOfWeek]].
     *
     * @param [[@doctodo param_type:dayOfWeek]] $dayOfWeek [[@doctodo param_description:dayOfWeek]]
     *
     * @return [[@doctodo return_type:isDayOfWeek]] [[@doctodo return_description:isDayOfWeek]]
     */
    public function isDayOfWeek($dayOfWeek)
    {
        $currentDayOfWeek = (int) date("w");

        return $currentDayOfWeek == $dayOfWeek;
    }
    /**
     * [[@doctodo method_description:isDayOfMonth]].
     *
     * @param [[@doctodo param_type:dayOfMonth]] $dayOfMonth [[@doctodo param_description:dayOfMonth]]
     *
     * @return [[@doctodo return_type:isDayOfMonth]] [[@doctodo return_description:isDayOfMonth]]
     */
    public function isDayOfMonth($dayOfMonth)
    {
        $currentDayOfMonth = (int) date("j");

        return $currentDayOfMonth == $dayOfMonth;
    }
}
