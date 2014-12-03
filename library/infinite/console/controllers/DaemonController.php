<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\controllers;

use Yii;
use infinite\base\exceptions\Exception;
use infinite\base\Daemon;

class DaemonController extends \infinite\console\Controller
{
    public function actionRun()
    {
        while (true) {
            $this->tick();
            sleep(5);
        }
    }

    protected function tick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->tick()) {
            $this->stderr("An error has occurred during a daemon tick.");
        }
    }
}
