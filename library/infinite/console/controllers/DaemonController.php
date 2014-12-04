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
ini_set('memory_limit', -1);

class DaemonController extends \infinite\console\Controller
{
    public function actionRun()
    {
        $cmd = [PHP_BINARY];
        $cmd[] = $_SERVER['PHP_SELF'];
        $cmd[] = 'daemon/tick';
        $cmd[] = '2>&1';
        while (true) {
            exec(implode(' ', $cmd), $output, $exitCode);
            sleep(5);
        }
    }

    public function actionTick()
    {
        $this->tick();
    }


    protected function tick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->tick()) {
            $this->stderr("An error has occurred during a daemon tick.");
        }
    }
}
