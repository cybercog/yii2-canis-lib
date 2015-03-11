<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\console\controllers;

use teal\base\Daemon;

ini_set('memory_limit', -1);

/**
 * DaemonController [[@doctodo class_description:teal\console\controllers\DaemonController]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DaemonController extends \teal\console\Controller
{
    /**
     * [[@doctodo method_description:actionRun]].
     */
    public function actionRun()
    {
        $this->runTick();
        $this->runPostTick();
    }

    /**
     * [[@doctodo method_description:runTick]].
     */
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

    /**
     * [[@doctodo method_description:runPostTick]].
     */
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

    /**
     * [[@doctodo method_description:actionTick]].
     */
    public function actionTick()
    {
        $this->tick();
    }

    /**
     * [[@doctodo method_description:actionPostTick]].
     */
    public function actionPostTick()
    {
        $this->postTick();
    }

    /**
     * [[@doctodo method_description:tick]].
     */
    protected function tick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->tick()) {
            $this->stderr("An error has occurred during a daemon tick.");
        }
    }
    /**
     * [[@doctodo method_description:postTick]].
     */
    protected function postTick()
    {
        $instance = Daemon::getInstance();
        if (!$instance->postTick()) {
            $this->stderr("An error has occurred during a daemon post-tick.");
        }
    }
}
