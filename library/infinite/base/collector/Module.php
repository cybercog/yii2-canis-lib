<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use Yii;
use yii\base\Event;

/**
 * Module [@doctodo write class description for Module].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Module extends Collector
{
    const EVENT_AFTER_LOAD = 'afterLoad';

    /**
     */
    public $autoload = true;
    /**
     */
    protected $_loaded = false;

    /**
     * Get module prefix.
     */
    abstract public function getModulePrefix();

    /**
     * @inheritdoc
     */
    public function beforeRequest(Event $event)
    {
        $this->load();

        return parent::beforeRequest($event);
    }

    /**
     *
     */
    public function load($force = false)
    {
        if (!$this->_loaded && ($force || $this->autoload)) {
            $this->_loaded = true;
            Yii::beginProfile($this->modulePrefix . '::load');
            foreach (Yii::$app->modules as $module => $settings) {
                if (preg_match('/^' . $this->modulePrefix . '/', $module) === 0) {
                    continue;
                }
                Yii::beginProfile($this->modulePrefix . '::load::' . $module);
                $mod = Yii::$app->getModule($module);
                Yii::endProfile($this->modulePrefix . '::load::' . $module);
            }
            $this->trigger(self::EVENT_AFTER_LOAD);
            Yii::endProfile($this->modulePrefix . '::load');
        }
    }

    /**
     *
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }
}
