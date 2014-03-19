<?php
/**
 * library/security/role/Engine.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security\role;

use Yii;
use infinite\helpers\ArrayHelper;

class Collector extends \infinite\base\collector\Collector
{
	protected $_tableRegistry;
	protected $_initialItems = [];
	public function getInitialItems()
	{
		return $this->_initialItems;
	}

	public function setInitialItems($value)
	{
		$this->_initialItems = $value;
	}

    public function getCollectorItemClass() {
		return 'infinite\\security\\role\\Item';
	}

	public function getById($id)
	{
		foreach ($this->tableRegistry as $role) {
			if ($role->primaryKey === $id) {
				$object = $this->getOne($role->system_id);
				if ($object->object) {
					return $object->object;
				}
				break;
			}
		}
		return false;
	}

	public function getTableRegistry() {
		if (is_null($this->_tableRegistry)) {
			$roleClass = Yii::$app->classes['Role'];
			$this->_tableRegistry = [];
			if ($roleClass::tableExists()) {
				$om = $roleClass::find()->all();
				$this->_tableRegistry = ArrayHelper::index($om, 'system_id');
			}
		}
		return $this->_tableRegistry;
	}

	public function prepareComponent($component) {
		if (!Yii::$app->isDbAvailable) { return $component; }
		if (!isset($component['systemId'])) { return false; }
		$roleClass = Yii::$app->classes['Role'];
		$component['object'] = isset($this->tableRegistry[$component['systemId']]) ? $this->tableRegistry[$component['systemId']] : false;
		if (empty($component['object'])) {
			$component['object'] = new $roleClass;
			$component['object']->name = $component['name'];
			$component['object']->system_id = $component['systemId'];
			if (!$component['object']->save()) {
				throw new Exception("Couldn't save new role {$component['systemId']} ". print_r($component['object']->getFirstErrors(), true));
			}
			$this->_tableRegistry[$component['systemId']] = $component['object'];
			Yii::trace("Role has been initialized {$component['name']} ({$component['systemId']})");
		}
		return $component;
	}

}
