<?php
namespace infinite\base;

use Yii;

use \infinite\base\Component;

use \yii\base\Application;
use \yii\base\Event;

abstract class ModuleCollector extends \infinite\base\Collector {
	const EVENT_AFTER_LOAD = 'afterLoad';

	abstract public function getModulePrefix();

	 /**
     *
     */
    public function init()
    {
        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, array($this, 'beforeRequest'));
        parent::init();
    }

    public function onAfterLoad($action) {
    	return $this->on(self::EVENT_AFTER_LOAD, $action);
    }

	public function beforeRequest(Event $event) {
		$this->load();
		return true;
	}

	public function load() {
		Yii::beginProfile($this->modulePrefix .'::load');
		foreach (Yii::$app->modules as $module => $settings) {
			if (preg_match('/^'.$this->modulePrefix.'/', $module) === 0) { continue; }
			$mod = Yii::$app->getModule($module);
		}
		$this->trigger(self::EVENT_AFTER_LOAD);
		Yii::endProfile($this->modulePrefix .'::load');
	}
}
?>