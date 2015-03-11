<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

/**
 * Daemon [[@doctodo class_description:teal\base\Daemon]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Daemon extends Component
{
    // should be used for quick, maintenance tasks
    const EVENT_TICK = '__DAEMON_TICK';
    const EVENT_POST_TICK = '__DAEMON_POST_TICK';

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
     * [[@doctodo method_description:tick]].
     *
     * @return [[@doctodo return_type:tick]] [[@doctodo return_description:tick]]
     */
    public function tick()
    {
        $event = new DaemonEvent();
        $this->trigger(static::EVENT_TICK, $event);

        return $event->isValid;
    }

    /**
     * [[@doctodo method_description:postTick]].
     *
     * @return [[@doctodo return_type:postTick]] [[@doctodo return_description:postTick]]
     */
    public function postTick()
    {
        $event = new DaemonEvent();
        $this->trigger(static::EVENT_POST_TICK, $event);

        return $event->isValid;
    }
}
