<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\console\controllers;

use teal\base\Cron;

ini_set('memory_limit', -1);

/**
 * CronController [[@doctodo class_description:teal\console\controllers\CronController]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class CronController extends \teal\console\Controller
{
    /**
     * [[@doctodo method_description:actionIndex]].
     */
    public function actionIndex()
    {
        $instance = Cron::getInstance();
        if ($instance->hourly()) {
            $this->stdout("Cron ran successfully");
        } else {
            $this->stderr("An error has occurred!");
        }
    }
    /**
     * [[@doctodo method_description:actionTriggerMidnight]].
     */
    public function actionTriggerMidnight()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_MIDNIGHT);
    }
    /**
     * [[@doctodo method_description:actionTriggerHourly]].
     */
    public function actionTriggerHourly()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_HOURLY);
    }

    /**
     * [[@doctodo method_description:actionTriggerMorning]].
     */
    public function actionTriggerMorning()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_MORNING);
    }
    /**
     * [[@doctodo method_description:actionTriggerEvening]].
     */
    public function actionTriggerEvening()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_EVENING);
    }
    /**
     * [[@doctodo method_description:actionTriggerWeekly]].
     */
    public function actionTriggerWeekly()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_WEEKLY);
    }
    /**
     * [[@doctodo method_description:actionTriggerMonthly]].
     */
    public function actionTriggerMonthly()
    {
        $instance = Cron::getInstance();
        $instance->trigger(Cron::EVENT_MONTHLY);
    }
}
