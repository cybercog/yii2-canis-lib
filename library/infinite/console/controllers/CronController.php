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
}
