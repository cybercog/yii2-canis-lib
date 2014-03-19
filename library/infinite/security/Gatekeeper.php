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
use yii\caching\DbDependency;
use yii\caching\ChainedDependency;

class Gatekeeper extends \infinite\base\Component
{
	public $proxy = false;

	protected $_aros;
	protected $_actionsById;
	protected $_actionsByName;
	protected $_primaryAro;

	static $_cache = [];

	protected $_objectCanCache = [];
	
	public $authorityClass = 'infinite\\security\\Authority';
	public $accessClass = 'infinite\\security\\Access';
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

	public function getAclCacheDependency()
	{
		$aclClass = Yii::$app->classes['Acl'];
		$query = new Query;
		$query->from([$aclClass::tableName() .' acl']);
		$query->orderBy(['modified' => SORT_DESC]);
		$query->select(['modified']);
		$query->limit(1);

		$acaClass = Yii::$app->classes['Aca'];


		return new ChainedDependency([
			'dependencies' => [
				new GroupDependency(['group' => 'aros']),
				$acaClass::cacheDependency(),
				new DbDependency(['reusable' => true, 'sql' => $query->createCommand()->rawSql])
			]
		]);
	}


	public function canPublic($controlledObject, $action = 'read') {
		$requestKey = md5(serialize([__FUNCTION__, func_get_args()]));
		if (!array_key_exists($requestKey, self::$_cache)) {
			self::$_cache[$requestKey] = false;
			$accessGroups = ['public', 'clients'];
			$accessObject = [];
			$groupModel = Yii::$app->classes['Group'];
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
			$accessingObject = $this->getAccessingObject($accessingObject);

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
		$registryClass = Yii::$app->classes['Registry'];
		foreach ($controlledObject as $co) {
			if (!is_object($co)) {
				$oco = $co;
				$co = $registryClass::getObject($co, false);
			}
			if ($co && $co->can($action, $accessingObject)) {
				return true;
			}
		}
		return false;
	}

	public function fillActions($acls, $baseAccess = [], $acaIds = null)
	{
		$nullValue = $this->findNullAction($acls);
		$noAccessValue = ActiveAccess::translateAccessValue(-1);
		$actions = $this->actionsById;
		// if (isset($acaIds) && is_array($acaIds)) {
		// 	foreach ($acaIds as $acaId) {
		// 		unset($actions[$acaId]);
		// 	}
		// }

		$access = [];
		foreach ($acls as $acl) {
			$acaValue = ArrayHelper::getValue($acl, 'aca_id');
			$accessValue = ActiveAccess::translateAccessValue(ArrayHelper::getValue($acl, 'access'));
			if (is_null($acaValue)) { continue; }
			if (!array_key_exists($acaValue, $baseAccess) 
				|| $baseAccess[$acaValue] === $noAccessValue) {
				$access[$acaValue] = $accessValue;
			}
		}

		foreach ($actions as $action) {
			$acaValue = ArrayHelper::getValue($action, 'id');
			if (!array_key_exists($acaValue, $access)) {
				$access[$acaValue] = $nullValue;
			}
		}
		return $access;
	}

	public function findNullAction($acls)
	{
		foreach ($acls as $acl) {
			$acaValue = ArrayHelper::getValue($acl, 'aca_id');
			if (is_null($acaValue)) {
				$access = ArrayHelper::getValue($acl, 'access');
				return ActiveAccess::translateAccessValue($access);
			}	
		}
		return ActiveAccess::translateAccessValue(-1);
	}

	public function canGeneral($action, $model, $accessingObject = null) {
		if (!is_object($model)) {
			$model = new $model;
		}
		return !$model->getBehavior('Access') || $model->can($action, $accessingObject);
	}

	public function getControlledObject($object, $modelClass = null)
	{
		if (!empty($object)) {
			if (!is_object($object)) {
				$registryClass = Yii::$app->classes['Registry'];
				$object = $registryClass::getObject($object, false);
				if (empty($object)) {
					return false;
				}
			}
			return $object;
		}
		return false;
	}

    public function generateAclCheckCriteria($query, $controlledObject, $accessingObject = null, $allowParentInherit = false, $modelClass = null, $expandAros = true) {
        $aclClass = Yii::$app->classes['Acl'];
        $alias = $aclClass::tableName();
        $modelAlias = null;
        if (is_null($modelClass)) {
        	$modelClass = get_class($controlledObject);
        } elseif(is_string($modelClass)) {
        	$modelClass = ActiveRecord::parseModelAlias($modelClass);
        } else {
        	$modelClass = null;
        }

        if (!is_null($modelClass)) {
        	$modelAlias = ActiveRecord::modelAlias($modelClass);
        }

		// get aro's 
		if ($expandAros) {
			$aros = $this->getAros($accessingObject);
		} elseif(isset($accessingObject)) {
			$aros = is_object($accessingObject) ? [$accessingObject->primaryKey] : [$accessingObject];
		} else {
			$aros = [];
		}

		$aclOrder = [];
		$aclOnConditions = ['and'];
		$aroN = 0;
		$aroIn = [];

		$aclOrder[$alias.'.access'] = SORT_ASC;

		$aclOrder['IF('.$alias.'.accessing_object_id IS NULL, 0, 1)'] = SORT_DESC;
		foreach ($aros as $aro) {
			if (is_array($aro)) {
				$subInIf = [];
				foreach ($aro as $sa) {
					$aroIn[] = $sa;
					$subInIf[] = $sa;
					$aroN++;
				}
			} else {
				$aroIn[] = $aro;
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

		$controlledObject = $this->getControlledObject($controlledObject, $modelClass);

		if (!empty($controlledObject)) {
			$innerOnConditions[] = [$alias.'.controlled_object_id' => $controlledObject];
		}

		$innerOnConditions[] = $alias.'.controlled_object_id =' .$query->primaryAlias .'.'. $query->primaryTablePk;
		$innerOnConditions[] = [$alias.'.controlled_object_id' => null];

		$aclOnConditions[] = $innerOnConditions;

		$aclClass = Yii::$app->classes['Acl'];

		if ($this->isAclQuery($query)) {
			if (!empty($aclConditions)) {
				$aclOnConditions[] = $aclConditions;
			}
			$query->andWhere($aclOnConditions);
		} else {
			$query->join('INNER JOIN', $aclClass::tableName() .' '. $alias .' USE INDEX(`aclComboAcaAccess`)', $aclOnConditions);
			$query->andWhere($aclConditions);
			$query->groupBy($query->primaryAlias .'.'. $query->primaryTablePk);
		}
		if (!isset($query->orderBy)) {
			$query->orderBy($aclOrder);
		} else {
			$query->orderBy = array_merge($aclOrder, $query->orderBy);
		}
    	return $query;
    }

    protected function isAclQuery(\yii\db\Query $query)
    {
    	$aclClass = Yii::$app->classes['Acl'];
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

	public function getAccess($controlledObject, $accessingObject = null, $acaIds = null, $expandAros = true)
	{
		if (!$this->primaryAro) { return []; }
		if (is_null($acaIds)) {
			$acaIds = true;
		}
		if (empty($acaIds)) {
			return [];
		}

		$aclKey = [__CLASS__.'.'.__FUNCTION__, func_get_args(), $this->primaryAro->primaryKey];
    	$access = Cacher::get($aclKey);
    	if ($access) {
    		return $access;
    	}

		$query = new Query;
		$aclClass = Yii::$app->classes['Acl'];
		$alias = $aclClass::tableName();
		$query->from = [$aclClass::tableName() .' '. $alias];
		$this->generateAclCheckCriteria($query, $controlledObject, $accessingObject, true, get_class($controlledObject), $expandAros);
		if ($acaIds !== true) {
			$query->andWhere(['or', [$alias.'.aca_id' => $acaIds], [$alias.'.aca_id' => null]]);
		}
		$query->groupBy($query->primaryAlias .'.aca_id');
		$raw = $query->all();
		$results = $this->fillActions($raw, [], $acaIds);
		
		Cacher::set($aclKey, $results, 0, $this->aclCacheDependency);
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
    			$userClass = Yii::$app->classes['User'];
    			$systemUser = $userClass::systemUser();
    			if ($systemUser) {
    				$this->_primaryAro = $systemUser;
    			}
    		} else {
    			$this->_primaryAro = $this->getPublicGroup();
    		}
    	}
    	return $this->_primaryAro;
    }

    public function getAros($accessingObject = null) {
		$accessingObject = $this->getAccessingObject($accessingObject);
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
	    			$this->_aros[$arosKey][] = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
	    			$this->_aros[$arosKey] = array_merge($this->_aros[$arosKey], $this->getGroups($accessingObject, true));
	    		}
	    		if ($this->authority && ($requestors = $this->authority->getRequestors($accessingObject)) && $requestors) {
	    			$this->_aros[$arosKey] = array_merge($this->_aros[$arosKey], $requestors);
	    		}

				if ($this->getPublicGroup()) { // always allow public groups
					$this->_aros[$arosKey][] = $this->getPublicGroup()->primaryKey;
					$this->_aros[$arosKey][] = $this->getTopGroup()->primaryKey;
				}
				$this->_aros[$arosKey] = array_unique($this->_aros[$arosKey]);
				Cacher::set($arosKey, $this->_aros[$arosKey], 0, new GroupDependency(['group' => 'aros']));
			}
    	}
    	//\d(array_unique($this->_aros[$arosKey]));exit;
    	return array_unique($this->_aros[$arosKey]);
    }

    public function getAccessingObject($accessingObject)
    {
		if (is_null($accessingObject)) {
			$accessingObject = $this->primaryAro;
		}
		if (!is_object($accessingObject)) {
			$registryClass = Yii::$app->classes['Registry'];
			$accessingObject = $registryClass::getObject($accessingObject, false);
		}
		return $accessingObject;
    }

    public function getGroups($accessingObject = null, $flatten = false) {
		$requestKey = md5(serialize([__FUNCTION__, func_get_args()]));

		if (!isset(self::$_cache[$requestKey])) {
			$accessingObject = $this->getAccessingObject($accessingObject);
	    	$groups = [];
	    	$parents = $accessingObject->parents(Yii::$app->classes['Group'], [], ['disableAccessCheck' => 1]);
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
			$acaClass = Yii::$app->classes['Aca'];
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
		$acaClass = Yii::$app->classes['Aca'];
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

	public function clearActionsCache()
	{
		$this->_actionsByName = null;
		$this->_actionsById = null;
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
		$groupClass = Yii::$app->classes['Group'];
		return $groupClass::getBySystemName('top', $checkAccess);
	}

	public function clearExplicitRules($controlledObject, $accessingObject = false) {
		$params = [];
		$params['controlled_object_id'] = is_object($controlledObject) ? $controlledObject->primaryKey : $controlledObject;
		if ($accessingObject) {
			$params['accessing_object_id'] = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
		}
		$aclClass = Yii::$app->classes['Acl'];
		$current = $aclClass::find()->where($params)->all();
		foreach ($current as $acl) {
			$acl->delete();
		}
		return true;
	}

	public function allow($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, 1, $controlledObject, $accessingObject, $aclRole);
	}

	public function clear($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, false, $controlledObject, $accessingObject, $aclRole);
	}

	public function requireOwnerAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, -1, $controlledObject, $accessingObject, $aclRole);
	}

	public function requireAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, -2, $controlledObject, $accessingObject, $aclRole);
	}

	public function requireSuperAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, -3, $controlledObject, $accessingObject, $aclRole);
	}

	public function parentAccess($action, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		return $this->setAccess($action, 0, $controlledObject, $accessingObject, $aclRole);
	}


	public function setAccess($action, $access, $controlledObject = null, $accessingObject = null, $aclRole = null) {
		$fields = [];
		if (is_array($action)) {
			$results = [true];
			foreach ($action as $a) {
				$results[] = $this->setAccess($a, $access, $controlledObject, $accessingObject, $aclRole);
			}
			return min($results);
		}
		$accessingObject = $this->getAccessingObject($accessingObject);

		if (empty($accessingObject)) {
			$accessingObject = $this->getTopGroup();
		}

		if (is_object($accessingObject)) {
			$accessingObject = $accessingObject->primaryKey;
		}

		if (!is_null($aclRole) && is_object($aclRole)) {
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
		$aclClass = Yii::$app->classes['Acl'];
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
			if ($acl->isNewRecord || $acl->access != $access || $acl->acl_role_id !== $aclRole) {
				$acl->access = $access;
				$acl->acl_role_id = $aclRole;
				$acl->save();
				return $acl->save();
			}
		}
		return true;
	}


	public function getObjectAccess($object)
	{
		$accessClass = $this->accessClass;
		return $accessClass::get($object);
	}

	public function getObjectAros($object)
	{
		$aclClass = Yii::$app->classes['Acl'];
		$where = [];
		$where = ['controlled_object_id' => $this->getControlledObject($object)];
		$aros = $aclClass::find()->where($where)->groupBy(['[[accessing_object_id]]'])->select(['[[accessing_object_id]]'])->asArray()->all();
		$aros = ArrayHelper::getColumn($aros, 'accessing_object_id');
		return $aros;
	}

	public function getObjectRoles($object)
	{
		$aclRoleClass = Yii::$app->classes['AclRole'];
		$where = [];
		$where = ['controlled_object_id' => $this->getControlledObject($object)];
		$aros = $aclRoleClass::find()->where($where)->groupBy(['[[accessing_object_id]]'])->select(['[[accessing_object_id]]', '[[role_id]]'])->asArray()->all();
		$aros = ArrayHelper::map($aros, 'accessing_object_id', 'role_id');
		return $aros;
	}

	protected function getTopAccess($baseAccess = [])
	{
		$aclClass = Yii::$app->classes['Acl'];
		$base = $aclClass::find()->where(['[[accessing_object_id]]' => null, '[[controlled_object_id]]' => null])->asArray()->all();
		return $this->fillActions($base, $baseAccess);
	}


}
