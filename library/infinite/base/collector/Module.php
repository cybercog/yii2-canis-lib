<?php
namespace infinite\base\collector;

use Yii;

use \infinite\base\exceptions\Exception;

use \yii\base\Application;
use \yii\base\Event;

abstract class Module extends Collector {
	const EVENT_AFTER_LOAD = 'afterLoad';

	protected $_loaded = false;

	abstract public function getModulePrefix();

	public function beforeRequest(Event $event) {
		$this->load();
		return parent::beforeRequest($event);
	}

	public function load() {
		if (!$this->_loaded) {
			Yii::beginProfile($this->modulePrefix .'::load');
			foreach (Yii::$app->modules as $module => $settings) {
				if (preg_match('/^'.$this->modulePrefix.'/', $module) === 0) { continue; }
				Yii::beginProfile($this->modulePrefix .'::load::'.$module);
				$mod = Yii::$app->getModule($module);
				Yii::endProfile($this->modulePrefix .'::load::'.$module);
			}
			$this->trigger(self::EVENT_AFTER_LOAD);
			Yii::endProfile($this->modulePrefix .'::load');
			$this->_loaded = true;
		}
	}
	
    public function onAfterLoad($action) {
    	return $this->on(self::EVENT_AFTER_LOAD, $action);
    }
}
?>