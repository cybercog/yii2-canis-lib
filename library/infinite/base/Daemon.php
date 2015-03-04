<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

class Daemon extends Component
{
    // should be used for quick, maintenance tasks
    const EVENT_TICK = '__DAEMON_TICK';
    const EVENT_POST_TICK = '__DAEMON_POST_TICK';

    protected static $_instance;

    public static function getInstance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    public function tick()
    {
        $event = new DaemonEvent();
        $this->trigger(static::EVENT_TICK, $event);

        return $event->isValid;
    }

    public function postTick()
    {
        $event = new DaemonEvent();
        $this->trigger(static::EVENT_POST_TICK, $event);

        return $event->isValid;
    }
}
