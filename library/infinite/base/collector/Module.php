<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use Yii;

use yii\base\Event;

/**
 * Module [@doctodo write class description for Module]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
abstract class Module extends Collector
{
    const EVENT_AFTER_LOAD = 'afterLoad';

    /**
     * @var __var_autoload_type__ __var_autoload_description__
     */
    public $autoload = true;
    /**
     * @var __var__loaded_type__ __var__loaded_description__
     */
    protected $_loaded = false;

    /**
     * __method_getModulePrefix_description__
     */
    abstract public function getModulePrefix();

    /**
    * @inheritdoc
    **/
    public function beforeRequest(Event $event)
    {
        $this->load();

        return parent::beforeRequest($event);
    }

    /**
     * __method_load_description__
     * @param boolean $force __param_force_description__ [optional]
     */
    public function load($force = false)
    {
        if (!$this->_loaded && ($force || $this->autoload)) {
            $this->_loaded = true;
            Yii::beginProfile($this->modulePrefix .'::load');
            foreach (Yii::$app->modules as $module => $settings) {
                if (preg_match('/^'.$this->modulePrefix.'/', $module) === 0) { continue; }
                Yii::beginProfile($this->modulePrefix .'::load::'.$module);
                $mod = Yii::$app->getModule($module);
                Yii::endProfile($this->modulePrefix .'::load::'.$module);
            }
            $this->trigger(self::EVENT_AFTER_LOAD);
            Yii::endProfile($this->modulePrefix .'::load');
        }
    }

    /**
     * __method_onAfterLoad_description__
     * @param __param_action_type__ $action __param_action_description__
     * @return __return_onAfterLoad_type__ __return_onAfterLoad_description__
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }
}
