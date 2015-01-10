<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\controllers;

use Yii;
use infinite\base\exceptions\Exception;
use infinite\base\Cron;
ini_set('memory_limit', -1);

class CronController extends \infinite\console\Controller
{
	public function actionIndex()
	{
		$instance = Cron::getInstance();
		if ($instance->hourly()) {
			$this->stdout("Cron ran successfully");
		} else {
			$this->stderr("An error has occurred!");
		}
	}
	public function actionTriggerMidnight()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_MIDNIGHT);
	}
	public function actionTriggerHourly()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_HOURLY);
	}

	public function actionTriggerMorning()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_MORNING);
	}
	public function actionTriggerEvening()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_EVENING);
	}
	public function actionTriggerWeekly()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_WEEKLY);
	}
	public function actionTriggerMonthly()
	{
		$instance = Cron::getInstance();
		$instance->trigger(Cron::EVENT_MONTHLY);
	}
}
