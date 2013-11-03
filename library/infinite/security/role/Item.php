<?php
/**
 * library/security/role/Role.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security\role;

use Yii;

use \infinite\base\exceptions\Exception;

class Item extends \infinite\base\Object
{
	protected $_name;
	protected $_model;

	public $name;
	public $unique = false;
	public $system_id;
	public $system_version = 1;
	public $level = 0;

	public $_acas = array();
	
	public function getModel() {
		if (is_null($this->_model)) {
			$this->_model = Yii::$app->roleEngine->getRoleModel($this->system_id);
			if (empty($this->_model) AND !empty($this->name)) {
				$modelName = Engine::MODEL;
				$this->_model = new $modelName;
				$this->_model->name = $this->name;
				$this->_model->system_id = $this->system_id;
				$this->_model->system_version = $this->system_version;
				if (!$this->_model->save()) {
					throw new Exception("Problem registering role!");
				}
				Yii::$app->roleEngine->registerRoleModel($this->_model);
			}
		}
		return $this->_model;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	public function getId() {
		if (!$this->model) {
			return false;
		}
		return $this->model->id;
	}

	public function setAcas($value) {
		if (is_null($value)) {
			$this->_acas = null;
			return true;
		}
		$this->_acas = array();
		foreach ($value as $v) {
			$aca = Yii::$app->gk->getActionObjectByName($v);
			if (!$aca) { continue; }
			$this->_acas[] = $aca;
		}
	}

	public function getAcas() {
		return $this->_acas;
	}
	
	/**
	 *
	 *
	 * @param unknown $module
	 * @return unknown
	 */
	public function setModule($module) {
		$this->_module = $module;
		return true;
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function getModule() {
		if (is_null($this->_module)) {
			return false;
		}
		return $this->_module;
	}
}
