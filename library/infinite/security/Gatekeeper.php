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
use infinite\db\Query;
use infinite\db\ActiveQuery;
use infinite\db\behaviors\ActiveAccess;
use infinite\caching\Cacher;

use yii\db\Expression;
use yii\caching\GroupDependency;

class Gatekeeper extends \infinite\base\Component
{
	public $acaClass = 'app\\models\\Aca';
	public $aclClass = 'app\\models\\Acl';
	public $aclRoleClass = 'app\\models\\AclRole';
	public $groupClass = 'app\\models\\Group';
	public $registryClass = 'app\\models\\Registry';
	public $userClass = 'app\\models\\User';

	public $proxy = false;

	protected $_aros;
	protected $_actionsById;
	protected $_actionsByName;
	protected $_primaryAro;

	static $_cache = [];

	protected $_objectCanCache = [];
	
	public $authorityClass = 'infinite\\security\\Authority';
	protected $_authority;

	public function setAuthority($authority)
	{
		if (!is_object($authority)) {
			if (!isset($authority['class'])) {
				$authority['class'] = $this->authorityClass;
			}
			$authority = Yii::createObject($authority);
		}
		$this->_authority = $authority;
	}

	public function getAuthority()
	{
		return $this->_authority;
	}

	public function canPublic($controlledObject, $action = 'read') {
		$requestKey = md5(serialize([__FUNCTION__, func_get_args()]));
		if (!array_key_exists($requestKey, self::$_cache)) {
			self::$_cache[$requestKey] = false;
			$accessGroups = ['public', 'clients'];
			$accessObject = [];
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
		$requestKey = md5(serialize([__FUNCTION__, func_get_args()]));
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
		if (!is_array($controlledObject)) {
			$controlledObject = [$controlledObject];
		}
		$registryClass = $this->registryClass;
		foreach ($controlledObject as $co) {
			if (!is_object($co)) {
				$oco = $co;
				$co = $registryClass::getObject($co, false);
				if ($co && $co->can($action, $accessingObject)) {
					return true;
				}
			}
		}
		return false;
	}

	public function canGeneral($action, $model, $accessingObject = null) {
		if (!is_object($model)) {
			$model = new $model;
		}
		return !$model->getBehavior('Access') || $model->can($action, $accessingObject);
	}

    public function generateAclCheckCriteria($query, $controlledObject, $accessingObject = null, $model = null, $allowParentInherit = false) {
        $aclClass = Yii::$app->gk->aclClass;
        $alias = $aclClass::tableName();
		// get aro's 
		$aros = $this->getAros($accessingObject);

		$aclOrder = [];
		$aclOnConditions = ['and'];
		$aroN = 0;
		$aroIn = [];

		if (!isset($model) && is_object($controlledObject)) {
			$model = get_class($controlledObject);
		}

		if (isset($model) && $model) {
			$modelClass = ActiveRecord::parseModelAlias($model);
			$modelTable = $modelClass::tableName();
		}

		if (!is_null($model)) {
			$model = ActiveRecord::modelAlias($model);
		}

		$aclOrder[$alias.'.access'] = SORT_ASC;

		$aclOrder['IF('.$alias.'.accessing_object_id IS NULL, 0, 1)'] = SORT_DESC;
		foreach ($aros as $aro) {
			if (is_array($aro)) {
				$subInIf = [];
				foreach ($aro as $sa) {
					// $query->params[':aro_'.$aroN] = $sa;
					$aroIn[] = $sa;
					$subInIf[] = $sa;
					$aroN++;
				}
				// $aclOrder['IF('.$alias.'.accessing_object_id IN ('.implode(', ', $subInIf).'), 1, 0)'] = SORT_DESC;
			} else {
				//$query->params[':aro_'.$aroN] = $aro;
				$aroIn[] = $aro;
				// $aclOrder['IF('.$alias.'.accessing_object_id = :aro_'.$aroN.', 1, 0)'] = SORT_DESC;
				$aroN++;
			}
		}
		
		if (!empty($aroIn)) {
			$aclOnConditions[] = ['or', [$alias.'.accessing_object_id' => $aroIn], [$alias.'.accessing_object_id' => null]];
		} else {
			$aclOnConditions[] = [$alias.'.accessing_object_id' => null];
		}

		$aclOrder['IF('.$alias.'.aca_id IS NULL, 0, 1)'] = SORT_DESC;
		$aclOrder['IF('.$alias.'.controlled_object_id IS NULL, 0, 1)'] = SORT_DESC;
		
		$innerOnConditions = ['or'];

		if ($allowParentInherit) {
			$aclConditions = [$alias . '.access' => [0, 1]];
		} else {
			$aclConditions = [$alias . '.access' => 1];
		}

		$aclOrder['IF('.$alias.'.object_model IS NULL, 0, 1)'] =  SORT_DESC;

		if (is_object($controlledObject) && !empty($controlledObject->id)) {
			$innerOnConditions[] = [$alias.'.controlled_object_id' => $controlledObject->id];
		} elseif (isset($controlledObject) && is_string($controlledObject)) {
			$innerOnConditions[] = [$alias.'.controlled_object_id' => $controlledObject];
		}

		if (!is_null($model)) {
			$innerOnConditions[] = ['and', [$alias.'.controlled_object_id' => null], [$alias.'.object_model' => $model]];
		} elseif(!isset($controlledObject)) {
			
		}
		$innerOnConditions[] = ['and', [$alias.'.controlled_object_id' => null], [$alias.'.object_model' => null]];
		$aclOnConditions[] = $innerOnConditions;

		$aclClass = $this->aclClass;

		$addSelect = false;
		if (!isset($query->select)) {
			$query->select = [];
			$addSelect = true;
		}

		if ($this->isAclQuery($query)) {
			if (!empty($aclConditions)) {
				$aclOnConditions[] = $aclConditions;
			}
			$query->andWhere($aclOnConditions);
			$addSelect = false;
		} else {
			$query->join('INNER JOIN', $aclClass::tableName() .' '. $alias, $aclOnConditions);
			$query->andWhere($aclConditions);
			$addSelect = $addSelect && true;
			$query->distinct = true;
		}
		if (!isset($query->orderBy)) {
			$query->orderBy($aclOrder);
		} else {
			$query->orderBy = array_merge($aclOrder, $query->orderBy);
		}

		if ($addSelect && $modelTable) {

			// this messes up distinct!
			$query->select = ["$modelTable.*"];
			// $query->select[] = $alias .'.access';
			// $query->select[] = $alias .'.aca_id as aca_id';
		} else {
			//$query->select[] = $alias . '.*';
		}
    	return $query;
    }

    protected function isAclQuery(\yii\db\Query $query)
    {
    	$aclClass = $this->aclClass;
    	if ($query instanceof ActiveQuery) {
    		return $query->modelClass === $aclClass;
    	} else { // regular old query. Have to do this by table name
    		if ($query->primaryTable === $aclClass::tableName()) {
    			return true;
    		} else {
    			return false;
    		}
    	}
    }

	public function getGeneralAccess($model, $accessingObject = null) {
		return [];
	}

	public function getParentActionTranslations() {
		return [
			$this->getActionObjectByName('delete')->primaryKey => $this->getActionObjectByName('update')
		];
	}

	protected function _translateParentAction($action) {
		$translationMap = $this->getParentActionTranslations();
		if (isset($translationMap[$action->primaryKey])) {
			return $translationMap[$action->primaryKey];
		} else {
			return $action;
		}
	}

	public function getAccess($controlledObject, $accessingObject = null, $acaIds = null)
	{
		if (is_null($acaIds)) {
			$acaIds = array_keys($this->actionsById);
		}
		if (empty($acaIds)) {
			return [];
		}
		$query = new Query;
		$aclClass = $this->aclClass;
		$alias = $aclClass::tableName();
		$query->from = [$aclClass::tableName() .' '. $alias];
		// generateAclCheckCriteria($query, $controlledObject, $accessingObject = null, $model = null, $allowParentInherit = false) {
		$this->generateAclCheckCriteria($query, $controlledObject, $accessingObject, null, true);
		$query->andWhere(['or', [$alias.'.aca_id' => $acaIds], [$alias.'.aca_id' => null]]);
		$query->groupBy($query->primaryAlias .'.aca_id');
		$raw = $query->all();
		$results = [];
		$foundNull = false;
		foreach ($raw as $key => $result) {
			if (is_null($result['aca_id'])) {
				$foundNull = ActiveAccess::translateAccessValue($result['access']);
				unset($raw[$key]);
				break;
			} else {
				$results[$result['aca_id']] = ActiveAccess::translateAccessValue($result['access']);
			}
		}
			foreach ($acaIds as $acaId) {
				if ($foundNull !== false) {
					$results[$acaId] = $foundNull;
				}
				if (!isset($results[$acaId])) {
					$results[$acaId] = ActiveAccess::translateAccessValue(-1);
				}
			}

		return $results;
	}



	public function clearCanCache($controlledObject, $accessingObject = null) {
		// @todo mix this in with the caching solution
		$this->_objectCanCache = [];
	}

	public function getPrimaryAro() {
		if ($this->proxy) {
			return $this->proxy;
		}

    	if (is_null($this->_primaryAro)) {
    		if (isset(Yii::$app->user) && !Yii::$app->user->isGuest && !empty(Yii::$app->user->id)) {
    			$this->_primaryAro = Yii::$app->user->identity;
    		} elseif (Yii::$app instanceof \yii\console\Application) {
    			$userClass = $this->userClass;
    			$systemUser = $userClass::systemUser();
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
			$arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, $accessingObject->primaryKey]));
		} else {
			$arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, false]));
		}

    	if (!isset($this->_aros[$arosKey])) {
    		$this->_aros[$arosKey] = Cacher::get($arosKey);
    		if (!$this->_aros[$arosKey]) {
	    		$this->_aros[$arosKey] = [];
	    		if ($accessingObject) {
	    			$this->_aros[$arosKey][] = $accessingObject->primaryKey;
	    			$this->_aros[$arosKey] = array_merge($this->_aros[$arosKey], $this->getGroups($accessingObject, true));
	    		}

				if ($this->getPublicGroup()) { // always allow public groups
					$this->_aros[$arosKey][] = $this->getPublicGroup()->primaryKey;
					$this->_aros[$arosKey][] = $this->getTopGroup()->primaryKey;
				}
				Cacher::set($arosKey, $this->_aros[$arosKey], 0, new GroupDependency(['group' => 'aros']));
			}
    	}
    	return array_unique($this->_aros[$arosKey]);
    }

    public function getGroups($accessingObject = null, $flatten = false) {
		$requestKey = md5(serialize([__FUNCTION__, func_get_args()]));

		if (!isset(self::$_cache[$requestKey])) {
			if (is_null($accessingObject)) {
				$accessingObject = $this->primaryAro;
			}
	    	$groups = [];
	    	$parents = $accessingObject->parents($this->groupClass, [], ['disableAccessCheck' => 1]);
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
		$actions = $acaClass::find()->all();
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

	public function getPublicGroup() {
		return $this->getGroup('public');
	}

	public function getTopGroup() {
		return $this->getGroup('top');
	}

	public function getGroup($systemName, $checkAccess = false)
	{
		$groupClass = $this->groupClass;
		return $groupClass::getBySystemName('top', $checkAccess);
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
		$fields = ['controlled_object_id' => $controlledObject, 'accessing_object_id' => $accessingObject];
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
				$creatorRole = [$creatorRole];
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

	public function parentAccess($action, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		return $this->setAccess($action, 0, $controlledObject, $accessingObject, $controlledObjectModel, $aclRole);
	}


	public function setAccess($action, $access, $controlledObject = null, $accessingObject = null, $controlledObjectModel = null, $aclRole = null) {
		if (!is_null($controlledObjectModel)) {
			$controlledObjectModel = ActiveRecord::modelAlias($controlledObjectModel);
		}
		$fields = [];
		if (is_array($action)) {
			//$action = implode('.', $action);
			$results = [true];
			foreach ($action as $a) {
				$results[] = $this->setAccess($a, $access, $controlledObject, $accessingObject, $controlledObjectModel);
			}
			return min($results);
		}
		if (is_null($accessingObject)) {
			$accessingObject = $this->getPrimaryAro();
		}

		if (empty($accessingObject)) {
			$accessingObject = $this->getTopGroup();
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
