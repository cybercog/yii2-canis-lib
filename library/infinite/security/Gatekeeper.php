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
use infinite\caching\Cacher;

class Gatekeeper extends \infinite\base\Component
{
    public $proxy = false;
    public $debug = false;

    protected $_requestors;
    protected $_actionsById;
    protected $_actionsByName;
    protected $_primaryAro;

    static $_cache = [];

    protected $_objectCanCache = [];

    public $authorityClass = 'infinite\\security\\Authority';
    public $objectAccessClass = 'infinite\\security\\ObjectAccess';
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

        return Cacher::chainedDependency([
            Cacher::groupDependency('aros'),
            Cacher::groupDependency('acl_role'),
            $acaClass::cacheDependency(),
            Cacher::dbDependency($query->createCommand()->rawSql, true)
        ]);
    }

    public function canPublic($controlledObject, $action = 'read')
    {
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

    public function is($group, $accessingObject = null)
    {
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

    public function can($action, $controlledObject, $accessingObject = null)
    {
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

    public function fillActions($acls, $baseAccess = [], $controlledObject = null, $acaIds = null)
    {
        $baseNullAccess = $this->findNullAction($acls);
        $baseNoAccess = $this->createAccess(['accessLevel' => Access::ACCESS_NONE]);
        $actions = $this->actionsById;
        $accessClass = $this->accessClass;
        $access = [];
        if ($baseNullAccess) {
            foreach ($actions as $action) {
                $acaValue = ArrayHelper::getValue($action, 'id');
                $nullAccess = clone $baseNullAccess;
                $nullAccess->action = $action;
                $access[$acaValue] = $nullAccess;
            }
        }

        foreach ($acls as $acl) {
            $acaValue = ArrayHelper::getValue($acl, 'aca_id');
            if (is_null($acaValue)) { continue; }
            if (!isset($access[$acaValue])) {
                if (!array_key_exists($acaValue, $baseAccess)
                    || $baseAccess[$acaValue] !== $accessClass::ACCESS_GRANTED) {
                    $accessObject = $this->createAccess($acl);
                } else {
                    $accessObject = $this->createAccess(['access' => $baseAccess[$acaValue]]);
                }
                $access[$acaValue] = $accessObject;
            }
        }

        foreach ($actions as $action) {
            $acaValue = ArrayHelper::getValue($action, 'id');
            if (!array_key_exists($acaValue, $access)) {
                $actionLink = $this->getActionLink($action, $access, $controlledObject);
                if ($actionLink) {
                    $access[$acaValue] = $actionLink;
                } else {
                    $noAccess = clone $baseNoAccess;
                    $noAccess->action = $action;
                    $access[$acaValue] = $noAccess;
                }
            }
        }

        return $access;
    }

    protected function getActionMap($controlledObject = null)
    {
        return [];
    }

    protected function getActionLink($action, $accessMap = [], $controlledObject = null)
    {
        $actionId = ArrayHelper::getValue($action, 'id');
        $actionName = ArrayHelper::getValue($action, 'name');
        $baseActionName = static::getBaseActionName($actionName);
        $actionMap = $this->getActionMap($controlledObject);
        $actionsByName = $this->getActionsByName();
        $mappedAction = null;
        if (isset($actionMap[$actionName])
            && isset($actionsByName[$actionMap[$actionName]])) {
            $mappedAction = $actionsByName[$actionMap[$actionName]];
        } elseif (isset($actionMap[$baseActionName])
            && isset($actionsByName[$actionMap[$baseActionName]])) {
            $mappedAction = $actionsByName[$actionMap[$baseActionName]];
        }

        if (isset($mappedAction) && isset($accessMap[$mappedAction->primaryKey])) {
            return $accessMap[$mappedAction->primaryKey];
        }

        return false;
    }

    protected static function getBaseActionName($actionName)
    {
        $parts = explode(':', $actionName);

        return $parts[0];
    }

    protected function createAccess($acl, $config = [])
    {
        if (is_object($acl)) {
            $config['aclModel'] = $acl;
        } elseif (is_array($acl)) {
            if (isset($acl['id'])) {
                // we have a faux-aclModel
                $config['aclModel'] = $acl['id'];
                if (!isset($config['accessLevel'])) {
                    $config['accessLevel'] = $acl['access'];
                }
                if (!isset($config['action'])) {
                    $config['action'] = $acl['aca_id'];
                }
            } else {
                $config = array_merge($config, $acl);
            }
        } else {
            return false;
        }

        if (!isset($config['class'])) {
            $config['class'] = $this->accessClass;
        }

        return Yii::createObject($config);
    }

    public function findNullAction($acls)
    {
        foreach ($acls as $acl) {
            $acaValue = ArrayHelper::getValue($acl, 'aca_id');
            if (is_null($acaValue)) {
                return $this->createAccess($acl);
            }
        }

        return false;
        // return $this->createAccess(['accessLevel' => Access::ACCESS_NONE]);
    }

    public function canGeneral($action, $model, $accessingObject = null)
    {
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

    public function buildInnerRoleCheckConditions(&$innerOnConditions, $innerAlias, $query)
    {
        return true;
    }

    // this function is not possible because it loses inheritance from object types two levels up
    public function BADgenerateAclRoleCheckCriteria($query, $controlledObject, $accessingObject = null, $modelClass = null, $bannedRoles = [], $expandAros = true)
    {
        if (Yii::$app->gk->accessorHasGroup($accessingObject, ['administrators', 'super_administrators'])) {
            return $query;
        }
        //\d($bannedRoles);
        $aclRoleClass = Yii::$app->classes['AclRole'];
        $aclRoleTable = $aclRoleClass::tableName();
        $modelAlias = null;
        if (is_null($modelClass)) {
            $modelClass = get_class($controlledObject);
        } elseif (is_string($modelClass)) {
            $modelClass = ActiveRecord::parseModelAlias($modelClass);
        } else {
            $modelClass = null;
        }

        if (!is_null($modelClass)) {
            $modelAlias = ActiveRecord::modelAlias($modelClass);
        }
        $controlledObject = $this->getControlledObject($controlledObject, $modelClass);
        $orderControlledObject = [];
        if (is_array($controlledObject)) {
            $modelPrefix = $modelClass::modelPrefix();
            foreach ($controlledObject as $objectId) {
                if (preg_match('/^'. preg_quote($modelPrefix .'-') .'/', $objectId) !== 1) {
                    $orderControlledObject[] = $objectId;
                }
            }
        }
        $subquery = new Query;
        $innerAlias = 'inner_acl_role';
        $innerOnConditions = ['or'];
        $innerOnConditions[] = ['[['. $innerAlias .']].[[controlled_object_id]]' => $controlledObject];
        $innerOnConditions[] = '[['. $innerAlias .']].[[controlled_object_id]] = {{' .$query->primaryAlias .'}}.[['. $query->primaryTablePk .']]';
        $this->buildInnerRoleCheckConditions($innerOnConditions, $innerAlias, $query);

        if (!empty($bannedRoles)) {
            $innerOnConditions = ['and', $innerOnConditions, ['and', ['not', ['[['. $innerAlias.']].[[role_id]]' => $bannedRoles]]]];
        }
        $subquery->from([$aclRoleTable => $aclRoleTable]);
        if ($expandAros) {
            $aros = $this->getRequestors($accessingObject);
        } elseif (isset($accessingObject)) {
            $aros = is_object($accessingObject) ? [$accessingObject->primaryKey] : [$accessingObject];
        } else {
            $aros = [];
        }

        $aroIn = [];
        foreach ($aros as $aro) {
            if (is_array($aro)) {
                foreach ($aro as $sa) {
                    $aroIn[] = $sa;
                }
            } else {
                $aroIn[] = $aro;
            }
        }

        $where = ['and'];
        if (!empty($aroIn)) {
            $where[] = ['{{'. $aclRoleTable .'}}.[[accessing_object_id]]' => $aroIn];
        } else {
            $where[] = ['{{'. $aclRoleTable .'}}.[[accessing_object_id]]' => null]; //never!
        }

        // $subquery->where($innerOnConditions)->select(['[[role_id]]']);
        if (!empty($orderControlledObject)) {
            $subquery->orderBy(['IF([[controlled_object_id]] IN ("'. implode('", "', $orderControlledObject) .'"), 1, 0)' => SORT_ASC]);
        }
        // $subquery->limit = '1';
        $subquery->where($where);
        $query->leftJoin([$innerAlias => $subquery], $innerOnConditions);
        $query->groupBy('{{'. $query->primaryAlias .'}}.[['. $query->primaryTablePk .']]');
        $query->having(['and', '[[accessRoleCheck]] IS NOT NULL']);
        if (!isset($query->ensureSelect)) {
            $query->ensureSelect = [];
        }
        $query->ensureSelect[] = '{{inner_acl_role}}.[[role_id]] as accessRoleCheck';

        return $query;
    }

    public function generateAclCheckCriteria($query, $controlledObject, $accessingObject = null, $allowParentInherit = false, $modelClass = null, $expandAros = true, $limitAccess = true)
    {
        $aclClass = Yii::$app->classes['Acl'];
        $alias = $aclClass::tableName();
        $modelAlias = null;
        if (is_null($modelClass)) {
            $modelClass = get_class($controlledObject);
        } elseif (is_string($modelClass)) {
            $modelClass = ActiveRecord::parseModelAlias($modelClass);
        } else {
            $modelClass = null;
        }

        if (!is_null($modelClass)) {
            $modelAlias = ActiveRecord::modelAlias($modelClass);
        }

        // get aro's
        if ($expandAros) {
            $aros = $this->getRequestors($accessingObject);
        } elseif (isset($accessingObject)) {
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

        if ($limitAccess) {
            if ($allowParentInherit) {
                $aclConditions = [$alias . '.access' => [0, 1]];
            } else {
                $aclConditions = [$alias . '.access' => 1];
            }
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

    public function getGeneralAccess($model, $accessingObject = null)
    {
        return [];
    }

    public function getParentActionTranslations()
    {
        return [
            $this->getActionObjectByName('delete')->primaryKey => $this->getActionObjectByName('update')
        ];
    }

    public function translateParentAction($object,$action)
    {
        $translationMap = $this->getParentActionTranslations();
        if (isset($translationMap[$action->primaryKey])) {
            return $translationMap[$action->primaryKey];
        } else {
            return $action;
        }
    }

    public function getAccess($controlledObject, $accessingObject = null, $acaIds = null, $expandAros = true)
    {
        if (is_null($accessingObject) && !$this->primaryRequestor) { return []; }
        if (is_null($acaIds)) {
            $acaIds = true;
        }
        if (empty($acaIds)) {
            return [];
        }

        $aclKey = [
            __CLASS__.'.'.__FUNCTION__,
            is_object($controlledObject) ? $controlledObject->primaryKey : $controlledObject,
            is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject,
            is_object($acaIds) ? $acaIds->primaryKey : $acaIds,
            $expandAros,
            !empty($this->primaryRequestor) ? $this->primaryRequestor->primaryKey : null
        ];
        $access = Cacher::get($aclKey);
        if ($access) {
            return $access;
        }

        $subquery = new Query;
        $aclClass = Yii::$app->classes['Acl'];
        $alias = $aclClass::tableName();
        $subquery->from = [$aclClass::tableName() .' '. $alias];
        $this->generateAclCheckCriteria($subquery, $controlledObject, $accessingObject, true, get_class($controlledObject), $expandAros, false);
        if ($acaIds !== true) {
            $subquery->andWhere(['or', [$alias.'.[[aca_id]]' => $acaIds], [$alias.'.[[aca_id]]' => null]]);
        }

        $query = new Query;
        $query->from(['internal' => $subquery]);
        $query->groupBy('[[internal]].[[aca_id]]');


        $raw = $query->all();
        $results = $this->fillActions($raw, [], $controlledObject, $acaIds);

        Cacher::set($aclKey, $results, 0, $this->aclCacheDependency);

        return $results;
    }



    public function clearCanCache($controlledObject, $accessingObject = null)
    {
        // @todo mix this in with the caching solution
        $this->_objectCanCache = [];
    }

    public function getPrimaryRequestor()
    {
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

    public function getTopRequestors($accessingObject = null)
    {

        $accessingObject = $this->getAccessingObject($accessingObject);
        if (is_object($accessingObject)) {
            $arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, $accessingObject->primaryKey]));
        } else {
            $arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, false]));
        }

        if (!isset($this->_requestors[$arosKey])) {
            $this->_requestors[$arosKey] = [];
            if ($this->authority && ($requestors = $this->authority->getTopRequestors($accessingObject)) && $requestors) {
                $this->_requestors[$arosKey] = array_merge($this->_requestors[$arosKey], $requestors);
            }
        }

        return array_unique($this->_requestors[$arosKey]);
    }

    public function getRequestors($accessingObject = null)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        if (is_object($accessingObject)) {
            $arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, $accessingObject->primaryKey]));
        } else {
            $arosKey = md5(json_encode([__CLASS__.'.'.__FUNCTION__, false]));
        }

        if (!isset($this->_requestors[$arosKey])) {
            $this->_requestors[$arosKey] = Cacher::get($arosKey);
            if (!$this->_requestors[$arosKey]) {
                $this->_requestors[$arosKey] = [];
                if ($accessingObject) {
                    $this->_requestors[$arosKey][] = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
                    $this->_requestors[$arosKey] = array_merge($this->_requestors[$arosKey], $this->getGroups($accessingObject, true));
                }
                if ($this->authority && ($requestors = $this->authority->getRequestors($accessingObject)) && $requestors) {
                    $this->_requestors[$arosKey] = array_merge($this->_requestors[$arosKey], $requestors);
                }

                if ($this->getPublicGroup()) { // always allow public groups
                    $this->_requestors[$arosKey][] = $this->getPublicGroup()->primaryKey;
                    $this->_requestors[$arosKey][] = $this->getTopGroup()->primaryKey;
                }
                $this->_requestors[$arosKey] = array_unique($this->_requestors[$arosKey]);
                Cacher::set($arosKey, $this->_requestors[$arosKey], 0, Cacher::groupDependency('aros'));
            }
        }

        return array_unique($this->_requestors[$arosKey]);
    }

    public function accessorHasGroup($accessingObject, $groupSystemId)
    {
        if (!is_array($groupSystemId)) {
            $groupSystemId = [$groupSystemId];
        }
        $userClass = Yii::$app->classes['User'];
        $accessingObject = $this->getAccessingObject($accessingObject);
        if (get_class($accessingObject) !== $userClass) {
            return false;
        }
        $groups = $this->getAccessorGroups($accessingObject);
        foreach ($groups as $group) {
            if (in_array($group->system, $groupSystemId)) {
                return true;
            }
        }

        return false;
    }

    public function getAccessorGroups($accessingObject)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        $groupClass = Yii::$app->classes['Group'];
        $cacheKey = Cacher::key([__FUNCTION__, is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject], true);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = $accessingObject->parents($groupClass, [], ['disableAccessCheck' => true]);
        }

        return self::$_cache[$cacheKey];
    }

    public function getAccessingObject($accessingObject)
    {
        if (is_null($accessingObject)) {
            $accessingObject = $this->primaryRequestor;
        }
        if (!is_object($accessingObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $accessingObject = $registryClass::getObject($accessingObject, false);
        }

        return $accessingObject;
    }

    public function getGroups($accessingObject = null, $flatten = false)
    {
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

    public function getActionObjectByName($action)
    {
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

    protected function _getActions()
    {
        $acaClass = Yii::$app->classes['Aca'];
        $actions = $acaClass::find()->all();
        $this->_actionsByName = ArrayHelper::index($actions, 'name');
        $this->_actionsById = ArrayHelper::index($actions, 'id');

        return true;
    }

    public function getActionsByName()
    {
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

    public function getActionsById()
    {
        if (is_null($this->_actionsById)) {
            $this->_getActions();
        }

        return $this->_actionsById;
    }

    public function getPublicGroup()
    {
        return $this->getGroup('public');
    }

    public function getTopGroup()
    {
        return $this->getGroup('top');
    }

    public function getGroup($systemName, $checkAccess = false)
    {
        $groupClass = Yii::$app->classes['Group'];

        return $groupClass::getBySystemName($systemName, $checkAccess);
    }

    public function clearExplicitRules($controlledObject, $accessingObject = false)
    {
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

    public function allow($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 1, $controlledObject, $accessingObject, $aclRole);
    }

    public function clear($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, false, $controlledObject, $accessingObject, $aclRole);
    }

    public function requireDirectAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -1, $controlledObject, $accessingObject, $aclRole);
    }

    public function requireAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -2, $controlledObject, $accessingObject, $aclRole);
    }

    public function requireSuperAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -3, $controlledObject, $accessingObject, $aclRole);
    }

    public function parentAccess($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 0, $controlledObject, $accessingObject, $aclRole);
    }


    public function setAccess($action, $access, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
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
        $objectAccessClass = $this->objectAccessClass;

        return $objectAccessClass::get($object);
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

    public function getObjectInheritedRoles($object, $params = [])
    {
        if (!isset($params['ignoreControlled'])) {
            $params['ignoreControlled'] = [];
        }
        $params['ignoreControlled'][] = $object->primaryKey;

        return $this->getObjectRoles($object, $params);
    }

    public function getObjectRoles($object, $params = [])
    {
        $aclRoleClass = Yii::$app->classes['AclRole'];
        $where = [];
        $controlledObject = $this->getControlledObject($object, get_class($object), ['debug' => true]);
        if (!is_array($controlledObject)) {
            $controlledObject = [$controlledObject];
        }
        $topControlledObject = $controlledObject[0];

        if (isset($params['ignoreControlled'])) {
            $controlledObject = array_diff($controlledObject, $params['ignoreControlled']);
        }
        $where = ['controlled_object_id' => $controlledObject];
        $arosRaw = $aclRoleClass::find()->where($where)->select(['[[id]]','[[controlled_object_id]]','[[accessing_object_id]]', '[[role_id]]'])->asArray();
        $arosRaw = $arosRaw->all();
        $aros = [];
        foreach ($arosRaw as $aro) {
            if (isset($aros[$aro['accessing_object_id']])) { continue; }
            $aros[$aro['accessing_object_id']] = [
                'acl_role_id' => $aro['id'],
                'role_id' => $aro['role_id'],
                'inherited' => $topControlledObject !== $aro['controlled_object_id']
            ];
        }
        ArrayHelper::map($aros, 'accessing_object_id', 'role_id');

        return $aros;
    }

    protected function getTopAccess($baseAccess = [])
    {
        $aclClass = Yii::$app->classes['Acl'];
        $base = $aclClass::find()->where(['[[accessing_object_id]]' => null, '[[controlled_object_id]]' => null])->asArray()->all();

        return $this->fillActions($base, $baseAccess);
    }

}
