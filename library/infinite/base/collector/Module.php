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
 * Module [[@doctodo class_description:infinite\base\collector\Module]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Module extends Collector
{
    const EVENT_AFTER_LOAD = 'afterLoad';

    /**
     * @var [[@doctodo var_type:autoload]] [[@doctodo var_description:autoload]]
     */
    public $autoload = true;
    /**
     * @var [[@doctodo var_type:_loaded]] [[@doctodo var_description:_loaded]]
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
     * [[@doctodo method_description:load]].
     *
     * @param boolean $force [[@doctodo param_description:force]] [optional]
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
     * [[@doctodo method_description:onAfterLoad]].
     *
     * @return [[@doctodo return_type:onAfterLoad]] [[@doctodo return_description:onAfterLoad]]
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }
}
