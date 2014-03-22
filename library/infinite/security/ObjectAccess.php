<?php
namespace infinite\security;

use Yii;
use infinite\base\exceptions\Exception;
use yii\db\Query;
use infinite\security\Access;
use infinite\caching\Cacher;

class ObjectAccess extends \infinite\base\Component
{
	protected $_object;
	protected $_requestors;
	protected $_roles;
	protected $_visibility;
	protected $_tempCache = [];


	public function __sleep()
	{
		if (is_object($this->_object)) {
			$this->_object = $this->_object->primaryKey;
		}

		$keys = array_keys((array)$this);
		$bad = ["\0*\0_tempCache"];
		foreach($keys as $k => $key) {
			if (in_array($key, $bad)) {
				unset($keys[$k]);
			}
		}
		return $keys;
	}

	public static function get($object)
	{
		$objectId = is_object($object) ? $object->primaryKey : $object;
		$accessKey = [__CLASS__.'.'.__FUNCTION__, $objectId];
    	$accessObject = Cacher::get($accessKey);
    	if ($accessObject) {
    		return $accessObject;
    	}
    	$accessClass = get_called_class();
    	$accessObject = Yii::createObject(['class' => $accessClass, 'object' => $object]);
		Cacher::set($accessKey, $accessObject, 0, Yii::$app->gk->aclCacheDependency);
		return $accessObject;
	}

	public function load()
	{
		$this->roles;
		$this->requestors;
	}


	public function getRequestors()
	{
		if (is_null($this->_requestors)) {
			$this->_requestors = [];
			$aros = Yii::$app->gk->getObjectAros($this->object);

			foreach ($this->specialRequestors as $special => $requestor) {
				if (!in_array($requestor->primaryKey, $aros)) {
					$aros[] = $requestor->primaryKey;
				}
			}
			foreach ($aros as $aro) {
				$this->_requestors[$aro] = Yii::$app->gk->getAccess($this->object, $aro, null, false);
			}
		}
		return $this->_requestors;
	}

	public function getRoles()
	{
		if (is_null($this->_roles)) {
			$this->_roles = Yii::$app->gk->getObjectRoles($this->object);
			foreach ($this->specialRequestors as $special => $requestor) {
				if (!array_key_exists($requestor->primaryKey, $this->_roles)) {
					$this->_roles[$requestor->primaryKey] = null;
				}
			}
		}
		return $this->_roles;
	}

	public function getRoleObjects()
	{
		if (!isset($this->_tempCache['roled'])) {
			$this->_tempCache['roled'] = [];
			$registryClass = Yii::$app->classes['Registry'];
			foreach ($this->roles as $requestorId => $roleId) {
				$object = $registryClass::getObject($requestorId, true);
				$role = Yii::$app->collectors['roles']->getById($roleId);
				$this->_tempCache['roled'][$requestorId] = [
					'object' => $object,
					'role' => $role
				];
			}
		}
		return $this->_tempCache['roled'];
	}

	public function getSpecialRequestors()
	{
		return [
			'public' => Yii::$app->gk->publicGroup
		];
	}

	public function setObject($object)
	{
		$this->_object = $object;
		$this->load();
	}

	public function getObject()
	{
		if (!is_object($this->_object)) {
			$registryClass = Yii::$app->classes['Registry'];
			$this->_object = $registryClass::getObject($this->_object, false);
		}
		return $this->_object;
	}

	public function getRoleHelpText($roleItem)
	{
		return null;
	}

	public function determineVisibility()
	{
		$groupClass = Yii::$app->classes['Group'];
		$groupPrefix = $groupClass::modelPrefix();
		$publicGroup = Yii::$app->gk->publicGroup;
		$actions = Yii::$app->gk->actionsByName;
		$readAction = $actions['read'];
		$publicAro = isset($this->requestors[$publicGroup->primaryKey]) ? $this->requestors[$publicGroup->primaryKey] : false;
		if ($publicAro && $publicAro[$readAction->primaryKey] === Access::ACCESS_GRANTED) {
			return 'public';
		}

		foreach ($this->requestors as $aro => $access) {
			if (preg_match('/^'. $groupPrefix .'\-/', $aro) === 0) {
				return 'shared';
			}
		}

		return 'private';
	}

	public function getPossibleRoles($accessingObject = null)
	{
		$accessorRoleLevel = $this->getAccessorRoleLevel($accessingObject);
		$roles = [];
		$nullRole = [];
		$nullRole['id'] = 'none';
		$nullRole['item'] = null;
		$nullRole['label'] = 'No Access';
		$nullRole['available'] = true;
		$nullRole['level'] = 0;
		$roles['none'] = $nullRole;
		foreach (Yii::$app->collectors['roles']->getAll() as $roleItem) {
			$roles[$roleItem->id] = $roleItem->package;
			$roles[$roleItem->id]['available'] = $roleItem->level <= $accessorRoleLevel;
			$roles[$roleItem->id]['helpText'] = $this->getRoleHelpText($roleItem);

		}
		return $roles;
	}

	public function getVisibility()
	{
		if (is_null($this->_visibility)) {
			$this->_visibility = $this->determineVisibility();
		}
		return $this->_visibility;
	}

	public function getAccessorRoleLevel($accessingObject = null)
	{
		$accessingObject = Yii::$app->gk->getAccessingObject($accessingObject);

		// @todo base this on the actual user
		return 1000;
	}
}
?>