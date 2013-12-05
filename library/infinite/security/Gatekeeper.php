<?php
/**
 * library/security/Gatekeeper.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security;

use Yii;

use infinite\base\exceptions\Exception;
use infinite\helpers\ArrayHelper;
use infinite\db\ActiveRecord;

use yii\db\Query;
use yii\db\Expression;

class Gatekeeper extends \infinite\base\Component
{
	public $acaClass = 'app\models\Aca';
	public $aclClass = 'app\models\Acl';
	public $aclRoleClass = 'app\models\AclRole';
	public $groupClass = 'app\models\Group';
	public $registryClass = 'app\models\Registry';
	public $userClass = 'app\models\User';

	public $proxy = false;

	protected $_aros;
	protected $_actionsById;
	protected $_actionsByName;
	protected $_primaryAro;

	static $_cache = array();

	protected $_objectCanCache = array();

	public function canPublic($controlledObject, $action = 'read') {
		$requestKey = md5(serialize(array(__FUNCTION__, func_get_args())));
		if (!array_key_exists($requestKey, self::$_cache)) {
			self::$_cache[$requestKey] = false;
			$accessGroups = array('guests', 'clients');
			$accessObject = array();
			$groupModel = $this->groupClass;
			foreach ($accessGroups as $group) {
				$groupObject = $groupModel::getBySystemName($group, true);
				if ($groupObject) {
					$accessObject[] = $groupObject;
				}
			}
			foreach ($accessObject as $ao) {
				if ($this->can($action, $controlledObject, $ao)) {
					self::$_cache[$requestKey] = true;
					break;
				}
			}
		}
		return self::$_cache[$requestKey];
	}

	public function is($group, $accessingObject = null) {
		$requestKey = md5(serialize(array(__FUNCTION__, func_get_args())));
		if (!array_key_exists($requestKey, self::$_cache)) {
			if (is_null($accessingObject)) {
				$accessingObject = $this->primaryAro;
			}

			if (is_array($group)) {
				foreach ($group as $g) {
					if ($this->is($g, $accessingObject)) {
						return true;
					}
				}
				return false;
			}
			$groupObject = Group::getBySystemName($group, true);
			$groups = $this->getGroups($accessingObject);
			if (!$groups OR !$groupObject) {
				self::$_cache[$requestKey] = false;
			} else {
				self::$_cache[$requestKey] = in_array($groupObject->primaryKey, $groups);
			}
		}
		return self::$_cache[$requestKey];
	}

	public function can($action, $controlledObject, $accessingObject = null) {
		$access = $this->getAccess($controlledObject, $accessingObject);
		if (is_null($action)) {
			$acaKey = null;
		} else {
			$aca = $this->getActionObjectByName($action);
			$acaKey = $aca->primaryKey;
		}
		if (array_key_exists($acaKey, $access) AND $access[$acaKey] === true) {
			$can = true;
		} else {
			$can = false;
		}
		return $can;
	}

	public function canGeneral($action, $model, $accessingObject = null) {
		$access = $this->getGeneralAccess($model, $accessingObject);
		if (is_null($action)) {
			$acaKey = null;
		} else {
			$aca = $this->getActionObjectByName($action);
			$acaKey = $aca->primaryKey;
		}
		if (array_key_exists($acaKey, $access) AND $access[$acaKey] === true) {
			$can = true;
		} else {
			$can = false;
		}
		return $can;
	}

    public function generateAclCheckCriteria($query, $controlledObject, $accessingObject = null, $model = null) {
    	$alias = 't';
		// get aro's 
		$aros = $this->getAros($accessingObject);

		$aclOrder = array();
		$aclOnConditions = array();
		$aroN = 0;
		$aroIn = array();

		if (!is_null($model)) {
			$model = ActiveRecord::modelAlias($model);
		}

		// I'm not sure about this, but I think we want it to inherit.... NEVERMIND
		$aclOrder['IF('.$alias.'.access IS NULL, 0, 1)'] = SORT_DESC;

		// if ($allowInherit) {
		// 	$aclOnConditions[] = $alias.'.access IN (0, 1)';
		// } else {
		// 	$aclOnConditions[] = $alias.'.access = 1';
		// }

		$aclOrder['IF('.$alias.'.accessing_object_id IS NULL, 0, 1)'] = SORT_DESC;
		foreach ($aros as $aro) {
			if (is_array($aro)) {
				$subInIf = array();
				foreach ($aro as $sa) {
					$query->params[':aro_'.$aroN] = $sa;
					$aroIn[] = ':aro_'.$aroN;
					$subInIf[] = ':aro_'.$aroN;
					$aroN++;
				}
				$aclOrder['IF('.$alias.'.accessing_object_id IN ('.implode(', ', $subInIf).'), 1, 0)'] = SORT_DESC;
			} else {
				$query->params[':aro_'.$aroN] = $aro;
				$aroIn[] = ':aro_'.$aroN;
				$aclOrder['IF('.$alias.'.accessing_object_id = :aro_'.$aroN.', 1, 0)'] = SORT_DESC;
				$aroN++;
			}
		}
		
		if (!empty($aroIn)) {
			$aclOnConditions[] = ''.$alias.'.accessing_object_id IN ('.implode(', ', $aroIn).') OR '.$alias.'.accessing_object_id IS NULL';
		} else {
			$aclOnConditions[] = ''.$alias.'.accessing_object_id IS NULL';
		}



		$aclOrder['IF('.$alias.'.aca_id IS NULL, 0, 1)'] = SORT_DESC;
		$aclOrder['IF('.$alias.'.controlled_object_id IS NULL, 0, 1)'] = SORT_DESC;
		
		$innerOnConditions = array();

		if (is_object($controlledObject)) {
			$query->params[':object_model'] = $controlledObject->modelAlias;
			$query->params[':controlled_object_id'] = $controlledObject->id;
			$innerOnConditions[] = $alias.'.controlled_object_id=:controlled_object_id';
			$innerOnConditions[] = $alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model=:object_model';
			$aclOrder['IF('.$alias.'.object_model IS NULL, 0, 1)'] =  SORT_DESC;
		} elseif (!is_null($model)) {
			$query->params[':object_model'] = $model;
			$aclOrder['IF('.$alias.'.object_model IS NULL, 0, 1)'] =  SORT_DESC;
			$innerOnConditions[] = $alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model=:object_model';
		}elseif(is_null($controlledObject)) {
			$query->params[':object_model'] = $model;
			$aclOrder['IF('.$alias.'.object_model IS NULL, 0, 1)'] =  SORT_DESC;
			$innerOnConditions[] = $alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model IS NULL';
		} else {
			$cos = array();
			foreach ($controlledObject as $co) {
				$coKey = ':controlled_object_id_'.count($cos);
				$cos[] = $coKey;
				$query->params[$coKey] = $co;
			}
			$innerOnConditions[] = $alias.'.controlled_object_id IN ('.implode(',', $cos).')';

		}
		$query->select = [$alias.'.aca_id', $alias.'.access'];
		$innerOnConditions[] = $alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model IS NULL';
		$aclOnConditions[] = '('. implode(') OR (', $innerOnConditions) .')';

		if (isset($aclConditions)) {
			$aclOnConditions[] = $aclConditions;
		}

		$query->where = '('. implode(') AND (', $aclOnConditions) .')';
		$query->orderBy($aclOrder);
    	return $query;
    }


	public function getGeneralAccess($model, $accessingObject = null) {
		if (!is_null($model)) {
			$model = ActiveRecord::modelAlias($model);
		}

		$accessKey = md5(serialize(array(__FUNCTION__, $model, $accessingObject)));
		if (!array_key_exists($accessKey, $this->_objectCanCache)) {
			$this->_objectCanCache[$accessKey] = array();

	    	$aclClass = $this->aclClass;

			$innerAclQuery = new Query;
			$innerAclQuery->from($aclClass::tableName() .' t');
			$this->generateAclCheckCriteria($innerAclQuery, null, $accessingObject, $model);
			$innerAclCommand = $innerAclQuery->createCommand();
			$outerAclQuery = new Query;
			$outerAclQuery->from(['('. $innerAclCommand->sql .') `outer`']);
			$outerAclQuery->params($innerAclQuery->params);
			$outerAclQuery->select('outer.aca_id, outer.access');
			$outerAclQuery->groupBy('(`outer`.aca_id)');
			$raw = $outerAclQuery->all();
			$nullValue = null;
			$discoverParents = array();
			$acaClass = $this->acaClass;
			foreach ($raw as $r) {
				if (is_null($r['aca_id']) AND is_null($nullValue)) {
					$nullValue = $r['access'];
					if (empty($this->_objectCanCache[$accessKey])) {
						foreach ($acaClass::findAll() as $aca) {
							$this->_objectCanCache[$accessKey][$aca->id] = in_array($r['access'], array('0', '1'));
						}
					}
					continue;
				} elseif (!is_null($nullValue) AND is_null($r['access'])) {
					$r['access'] = $nullValue;
				}
				if ($r['access'] === '-1') {
					$this->_objectCanCache[$accessKey][$r['aca_id']] = false;
				} elseif ($r['access'] === '1') {
					$this->_objectCanCache[$accessKey][$r['aca_id']] = true;
				} elseif ($r['access'] === '0') {
					$this->_objectCanCache[$accessKey][$r['aca_id']] = true;
				}
				
			}
		}
		return $this->_objectCanCache[$accessKey];
	}

	public function getParentActionTranslations() {
		return array(
			$this->getActionObjectByName('delete')->primaryKey => $this->getActionObjectByName('update')
		);
	}

	protected function _translateParentAction($action) {
		$translationMap = $this->getParentActionTranslations();
		if (isset($translationMap[$action->primaryKey])) {
			return $translationMap[$action->primaryKey];
		} else {
			return $action;
		}
	}
	public function getAccess($controlledObject, $accessingObject = null) {
		$controlledId = null;
		if (is_object($controlledObject)) {
			$controlledId = $controlledObject->id;
		}
		$accessKey = md5(serialize(array($controlledId, $accessingObject)));
		if (!array_key_exists($accessKey, $this->_objectCanCache)) {
			$this->_objectCanCache[$accessKey] = array();
			$aclClass = $this->aclClass;

			$innerAclQuery = new Query;
			$innerAclQuery->from($aclClass::tableName() .' t');
			$this->generateAclCheckCriteria($innerAclQuery, null, $accessingObject);
			$innerAclCommand = $innerAclQuery->createCommand();

			$outerAclQuery = new Query;
			$outerAclQuery->from(['('. $innerAclCommand->sql .') `outer`']);
			$outerAclQuery->params($innerAclQuery->params);
			$outerAclQuery->select(['outer.aca_id', 'outer.access']);
			$outerAclQuery->groupBy('(`outer`.aca_id)');
			$raw = $outerAclQuery->all();

			$nullValue = null;
			$discoverParents = array();
			foreach ($raw as $r) {
				// @todo not sure if this needs to be done in another loop
				if (is_null($r['aca_id']) AND is_null($nullValue)) {
					$nullValue = $r['access'];
					continue;
				} elseif (!is_null($nullValue) AND is_null($r['access'])) {
					$r['access'] = $nullValue;
				}
				if ($r['access'] === '0') {
					$discoverParents[] = $r['aca_id'];
				} elseif ($r['access'] === '-1') {
					$this->_objectCanCache[$accessKey][$r['aca_id']] = false;
				} elseif ($r['access'] === '1') {
					$this->_objectCanCache[$accessKey][$r['aca_id']] = true;
				}
			}
			if (!is_null($nullValue)) {
				$acaClass = $this->acaClass;
				foreach ($acaClass::findAll() as $aca) {
					if (!isset($this->_objectCanCache[$accessKey][$aca->primaryKey])) {
						if ($nullValue === '0') {
							$discoverParents[] = $aca->primaryKey;
						} elseif ($nullValue === '-1') {
							$this->_objectCanCache[$accessKey][$aca->primaryKey] = false;
						} elseif ($nullValue === '1') {
							$this->_objectCanCache[$accessKey][$aca->primaryKey] = true;
						}
					}
				}
			}
			if (!empty($discoverParents)) {
				$parents = array();
				$parentIds = array();
				if (is_object($controlledObject) AND $controlledObject->hasBehavior('Relatable')) {
					$parentIds = $controlledObject->parentIds;
				}
				$acaById = $this->getActionsById();
				$registryClass = $this->registryClass;
				foreach ($discoverParents as $aca) {
					if (!isset($acaById[$aca])) { continue; }
					if (isset($this->_objectCanCache[$accessKey][$aca])) { continue; }
					$acaObject = $acaById[$aca];
					$this->_objectCanCache[$accessKey][$aca] = false;
					foreach ($parentIds as $parentId) {
						if (!isset($parents[$parentId])) {
							$parents[$parentId] = $registryClass::getObject($parentId, true);
						}
						if (isset($parents[$parentId])) {
							$testCan = $parents[$parentId]->can($this->_translateParentAction($acaObject));
							if ($testCan) {
								$this->_objectCanCache[$accessKey][$aca] = true;
								break;
							}
						}
					}
				}
			}
			
			//$this->_objectCanCache[$accessKey]
		}
		return $this->_objectCanCache[$accessKey];
	}

	public function clearCanCache($controlledObject, $accessingObject = null) {
		// @todo mix this in with the caching solution
		$this->_objectCanCache = array();
	}

	public function getPrimaryAro() {
		if ($this->proxy) {
			return $this->proxy;
		}

    	if (is_null($this->_primaryAro)) {
    		if (isset(Yii::$app->user) AND !Yii::$app->user->isGuest AND !empty(Yii::$app->user->id)) {
    			$this->_primaryAro = Yii::$app->user->identity;
    		} elseif (Yii::$app instanceof \yii\console\Application) {
    			$userClass = $this->userClass;
    			$systemUser = $userClass::find()->disableAccess()->where(['username' => 'system'])->one();
    			if ($systemUser) {
    				$this->_primaryAro = $systemUser;
    			}
    		} else {
    			$this->_primaryAro = false;
    		}
    	}
    	return $this->_primaryAro;
    }

    public function getAros($accessingObject = null) {
		if (is_null($accessingObject)) {
			$accessingObject = $this->primaryAro;
		}
    	if (is_object($accessingObject)) {
			$arosKey = md5(serialize(array(__FUNCTION__, $accessingObject->primaryKey)));
		} else {
			$arosKey = md5(serialize(array(__FUNCTION__, false)));
		}
    	if (!isset($this->_aros[$arosKey])) {
    		$this->_aros[$arosKey] = array();
    		if ($accessingObject) {
    			$this->_aros[$arosKey][] = $accessingObject->primaryKey;
    			$this->_aros[$arosKey] = array_merge($this->_aros[$arosKey], $this->getGroups($accessingObject, false));
    		}

			if ($this->getGuestGroup()) { // always allow guest groups
				$this->_aros[$arosKey][] = $this->getGuestGroup()->primaryKey;
				$this->_aros[$arosKey][] = $this->getTopGroup()->primaryKey;
			}
    	}
    	return $this->_aros[$arosKey];
    }

    public function getGroups($accessingObject = null, $flatten = false) {
		$requestKey = md5(serialize(array(__FUNCTION__, func_get_args())));

		if (!isset(self::$_cache[$requestKey])) {
			if (is_null($accessingObject)) {
				$accessingObject = $this->primaryAro;
			}
	    	$groups = array();
	    	$parents = $accessingObject->parents($this->groupClass, array(), array('disableAccess' => 1));
			if (!empty($parents)) {
				$children = ArrayHelper::getColumn($parents, 'primaryKey');
				if ($flatten) {
					$groups = array_merge($groups, $children);
				} else {
					$groups[] = $children;
				}
				foreach ($parents as $parent) {
					$groups = array_merge($groups, $this->getGroups($parent, $flatten));
				}
			}
			self::$_cache[$requestKey] = $groups;
		}
		return self::$_cache[$requestKey];
    }

	public function getActionObjectByName($action) {
		if (is_object($action)) { return $action; }
		$actions = $this->getActionsByName();
		if (!isset($actions[$action])) {
			$acaClass = $this->acaClass;
			$this->_actionsByName[$action] = $acaClass::find()->where(['name' => $action])->one();
			if (empty($this->_actionsByName[$action])) {
				$this->_actionsByName[$action] = new $acaClass;
				$this->_actionsByName[$action]->name = $action;
				if (!$this->_actionsByName[$action]->save()) {
					throw new Exception("Unable to start new action ($action)!");
				}
				$this->_actionsById[$this->_actionsByName[$action]->id] = $this->_actionsByName[$action];
			}
		}
		return $this->_actionsByName[$action];
	}

	protected function _getActions() {
		$acaClass = $this->acaClass;
		$actions = $acaClass::findAll();
		$this->_actionsByName = ArrayHelper::index($actions, 'name');
		$this->_actionsById = ArrayHelper::index($actions, 'id');
		return true;
	}
	public function getActionsByName() {
		if (is_null($this->_actionsByName)) {
			$this->_getActions();
		}
		return $this->_actionsByName;
	}


	public function getActionsById() {
		if (is_null($this->_actionsById)) {
			$this->_getActions();
		}
		return $this->_actionsById;
	}

	public function getGuestGroup() {
		$groupClass = $this->groupClass;
		return $groupClass::getBySystemName('guests', true);
	}

	public function getTopGroup() {
		$groupClass = $this->groupClass;
		return $groupClass::getBySystemName('top', true);
	}

	public function assignRole($role, $controlledObject, $accessingObject = null) {
		if (is_null($accessingObject)) {
			$accessingObject = $this->getPrimaryAro();
		}
		if (is_object($role)) {
			$role = $role->id;
		}
		if (is_object($accessingObject)) {
			$accessingObject = $accessingObject->primaryKey;
		}
		if (is_object($controlledObject)) {
			$controlledObject = $controlledObject->primaryKey;
		}
		$aclRoleModel = $this->aclRoleClass;
		$fields = array('controlled_object_id' => $controlledObject, 'accessing_object_id' => $accessingObject);
		$aclRole = $aclRoleModel::model()->field($fields)->find();
		if ($aclRole) {
			if (empty($role)) {
				return $aclRole->delete();
			}
			if ($aclRole->role_id !== $role) {
				$aclRole->role_id = $role;
				return $aclRole->save();
			}
		} else {
			$aclRole = new $aclRoleModel;
			$aclRole->attributes = $fields;
			$aclRole->role_id = $role;
			return $aclRole->save();
		}
		return true;
	}

	public function assignCreationRole($controlledObject, $accessingObject = null) {
		if (is_null($accessingObject)) {
			$accessingObject = $this->getPrimaryAro();
		}
		if (isset($accessingObject->isSystemUser) AND $accessingObject->isSystemUser === true) {
			return true;
		}
		if (isset($controlledObject->typeModule) AND $controlledObject->typeModule) {
			$module = $controlledObject->typeModule;
			$possibleRoles = ArrayHelper::index($module->possibleRoles, 'system_id');
			$creatorRole = $module->creatorRole;
			if ($creatorRole === false) { return true; }
			if (!is_array($creatorRole)) {
				$creatorRole = array($creatorRole);
			}
			foreach ($creatorRole as $role) {
				if (isset($possibleRoles[$role])) {
					return $this->assignRole($possibleRoles[$role], $controlledObject, $accessingObject);
				}
			}
			return $this->assignRole(null, $controlledObject, $accessingObject);
		} else {
			return $this->allow(null, $controlledObject, $accessingObject, $controlledObject->modelAlias);
		}
		
	}

	public function allow($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, 1, $controlledObject, $accessingObject, $controlledObjectModel, $aclRole);
	}

	public function clear($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, false, $controlledObject, $accessingObject, $controlledObjectModel, $aclRole);
	}

	public function deny($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, -1, $controlledObject, $accessingObject, $controlledObjectModel, $aclRole);
	}

	public function inherit($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, null, $controlledObject, $accessingObject , $controlledObjectModel, $aclRole);
	}

	public function parentAccess($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, 0, $controlledObject, $accessingObject, $controlledObjectModel, $aclRole);
	}


	public function setAccess($action, $access, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		if (!is_null($controlledObjectModel)) {
			$controlledObjectModel = ActiveRecord::modelAlias($controlledObjectModel);
		}

		$fields = array();
		if (is_array($action)) {
			//$action = implode('.', $action);
			$results = array(true);
			foreach ($action as $a) {
				$results[] = $this->setAccess($a, $access, $controlledObject, $accessingObject, $controlledObjectModel);
			}
			return min($results);
		}
		if (is_null($accessingObject)) {
			$accessingObject = $this->getPrimaryAro();
		}

		if (is_object($accessingObject)) {
			$accessingObject = $accessingObject->primaryKey;
		}

		if (!is_null($aclRole) AND is_object($aclRole)) {
			$aclRole = $aclRole->primaryKey;
		}

		if (empty($accessingObject)) {
			return false;
		}

		if (!is_null($controlledObject)) {
			$this->clearCanCache($controlledObject, $accessingObject);
		}
		if (is_object($controlledObject)) {
			$controlledObject = $controlledObject->primaryKey;
		}
		if (is_null($action)) {
			$actionObject = null;
			$fields['aca_id'] = null;
		} else {
			$actionObject = $this->getActionObjectByName($action);
			$fields['aca_id'] = $actionObject->id;
			if (empty($actionObject)) {
				throw new Exception("Unable to get ACL action $action");
			}
		}
		$fields['accessing_object_id'] = $accessingObject;
		$fields['controlled_object_id'] = $controlledObject;
		$fields['object_model'] = $controlledObjectModel;
		$aclClass = $this->aclClass;
		$acl = $aclClass::find()->where($fields)->one();
		if (empty($acl)) {
			$acl = new $aclClass;
			$acl->attributes = $fields;
		}
		if ($access === false) {
			if (!$acl->isNewRecord) {
				return $acl->delete();
			} else {
				return true;
			}
		} else {
			if ($acl->isNewRecord OR $acl->access != $access OR $acl->acl_role_id !== $aclRole) {
				$acl->access = $access;
				$acl->acl_role_id = $aclRole;
				$acl->save();
				//\infinite\base\Debug::d($acl->attributes);exit;
				return $acl->save();
			}
		}
		return true;
	}
}
