<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\controllers;

use infinite\base\Daemon;

ini_set('memory_limit', -1);

class DaemonController extends \infinite\console\Controller
{
    public function actionRun()
    {
        $this->runTick();
        $this->runPostTick();
    }

    protected function runTick()
    {
        $cmd = [PHP_BINARY];
        $cmd[] = $_SERVER['PHP_SELF'];
        $cmd[] = 'daemon/tick';
        $cmd[] = '2>&1';
        while (true) {
            exec(implode(' ', $cmd), $output, $exitCode);
            if (!empty($exitCode)) {
                \d($output);
            }
            sleep(5);
        }
    }

    protected function runPostTick()
    {
        $cmd = [PHP_BINARY];
        $cmd[] = $_SERVER['PHP_SELF'];
        $cmd[] = 'daemon/postTick';
        $cmd[] = '2>&1';
        while (true) {
            exec(implode(' ', $cmd), $output, $exitCode);
            if (!empty($exitCode)) {
                \d($output);
            }
            sleep(5);
        }
    }

    public function actionTick()
    {
        $this->tick();
    }

    public function actionPostTick()
    {
        $this->postTick();
    }

    protected function tick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->tick()) {
            $this->stderr("An error has occurred during a daemon tick.");
        }
    }
    protected function postTick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->postTick()) {
            $this->stderr("An error has occurred during a daemon post-tick.");
        }
    }
}
