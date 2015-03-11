<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security;

use teal\base\exceptions\Exception;
use teal\caching\Cacher;
use teal\db\ActiveQuery;
use teal\db\ActiveRecord;
use teal\db\Query;
use teal\helpers\ArrayHelper;
use Yii;

/**
 * Gatekeeper [[@doctodo class_description:teal\security\Gatekeeper]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Gatekeeper extends \teal\base\Component
{
    /**
     * @var [[@doctodo var_type:proxy]] [[@doctodo var_description:proxy]]
     */
    public $proxy = false;
    /**
     * @var [[@doctodo var_type:debug]] [[@doctodo var_description:debug]]
     */
    public $debug = false;

    /**
     * @var [[@doctodo var_type:_requestors]] [[@doctodo var_description:_requestors]]
     */
    protected $_requestors;
    /**
     * @var [[@doctodo var_type:_actionsById]] [[@doctodo var_description:_actionsById]]
     */
    protected $_actionsById;
    /**
     * @var [[@doctodo var_type:_actionsByName]] [[@doctodo var_description:_actionsByName]]
     */
    protected $_actionsByName;
    /**
     * @var [[@doctodo var_type:_primaryAro]] [[@doctodo var_description:_primaryAro]]
     */
    protected $_primaryAro;

    /**
     * @var [[@doctodo var_type:_cache]] [[@doctodo var_description:_cache]]
     */
    public static $_cache = [];
    /**
     * @var [[@doctodo var_type:_objectCanCache]] [[@doctodo var_description:_objectCanCache]]
     */
    protected $_objectCanCache = [];
    /**
     * @var [[@doctodo var_type:authorityClass]] [[@doctodo var_description:authorityClass]]
     */
    public $authorityClass = 'teal\security\Authority';
    /**
     * @var [[@doctodo var_type:objectAccessClass]] [[@doctodo var_description:objectAccessClass]]
     */
    public $objectAccessClass = 'teal\security\ObjectAccess';
    /**
     * @var [[@doctodo var_type:accessClass]] [[@doctodo var_description:accessClass]]
     */
    public $accessClass = 'teal\security\Access';
    /**
     * @var [[@doctodo var_type:_authority]] [[@doctodo var_description:_authority]]
     */
    protected $_authority;

    /**
     * [[@doctodo method_description:clearCache]].
     */
    public function clearCache()
    {
        static::$_cache = [];
        $this->_primaryAro = null;
        $this->_requestors = null;
        $this->_objectCanCache = [];
    }
    /**
     * Set authority.
     *
     * @param [[@doctodo param_type:authority]] $authority [[@doctodo param_description:authority]]
     */
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

    /**
     * Get authority.
     *
     * @return [[@doctodo return_type:getAuthority]] [[@doctodo return_description:getAuthority]]
     */
    public function getAuthority()
    {
        return $this->_authority;
    }

    /**
     * Get acl cache dependency.
     *
     * @return [[@doctodo return_type:getAclCacheDependency]] [[@doctodo return_description:getAclCacheDependency]]
     */
    public function getAclCacheDependency()
    {
        $aclClass = Yii::$app->classes['Acl'];
        $query = new Query();
        $query->from([$aclClass::tableName() . ' acl']);
        $query->orderBy(['modified' => SORT_DESC]);
        $query->select(['modified']);
        $query->limit(1);

        $acaClass = Yii::$app->classes['Aca'];

        return Cacher::chainedDependency([
            Cacher::groupDependency('aros'),
            Cacher::groupDependency('acl_role'),
            $acaClass::cacheDependency(),
            Cacher::dbDependency($query->createCommand()->rawSql, true),
        ]);
    }

    /**
     * [[@doctodo method_description:canPublic]].
     *
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param string                                   $action           [[@doctodo param_description:action]] [optional]
     *
     * @return [[@doctodo return_type:canPublic]] [[@doctodo return_description:canPublic]]
     */
    public function canPublic($controlledObject, $action = 'read')
    {
        $requestKey = md5(json_encode([__FUNCTION__, func_get_args()]));
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

    /**
     * [[@doctodo method_description:is]].
     *
     * @param [[@doctodo param_type:group]]           $group           [[@doctodo param_description:group]]
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:is]] [[@doctodo return_description:is]]
     */
    public function is($group, $accessingObject = null)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        $accessingObjectKey = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
        $requestKey = md5(json_encode([__FUNCTION__, func_get_args(), $accessingObjectKey]));
        if (!array_key_exists($requestKey, self::$_cache)) {
            if (is_array($group)) {
                foreach ($group as $g) {
                    if ($this->is($g, $accessingObject)) {
                        return true;
                    }
                }

                return false;
            }
            $groupClass = Yii::$app->classes['Group'];
            $groupObject = $groupClass::getBySystemName($group, false);
            $groups = $this->getGroups($accessingObject);
            if (!$groups || !$groupObject) {
                self::$_cache[$requestKey] = false;
            } else {
                $found = false;
                foreach ($groups as $groupSet) {
                    if (in_array($groupObject->primaryKey, $groupSet)) {
                        $found = true;
                        break;
                    }
                }
                self::$_cache[$requestKey] = $found;
            }
        }

        return self::$_cache[$requestKey];
    }

    /**
     * [[@doctodo method_description:can]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:can]] [[@doctodo return_description:can]]
     */
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

    /**
     * [[@doctodo method_description:fillActions]].
     *
     * @param [[@doctodo param_type:acls]]             $acls             [[@doctodo param_description:acls]]
     * @param array                                    $baseAccess       [[@doctodo param_description:baseAccess]] [optional]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:acaIds]]           $acaIds           [[@doctodo param_description:acaIds]] [optional]
     *
     * @return [[@doctodo return_type:fillActions]] [[@doctodo return_description:fillActions]]
     */
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
            if (is_null($acaValue)) {
                continue;
            }
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

    /**
     * Get action map.
     *
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     *
     * @return [[@doctodo return_type:getActionMap]] [[@doctodo return_description:getActionMap]]
     */
    protected function getActionMap($controlledObject = null)
    {
        return [];
    }

    /**
     * Get action link.
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param array                                    $accessMap        [[@doctodo param_description:accessMap]] [optional]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     *
     * @return [[@doctodo return_type:getActionLink]] [[@doctodo return_description:getActionLink]]
     */
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

    /**
     * Get base action name.
     *
     * @param [[@doctodo param_type:actionName]] $actionName [[@doctodo param_description:actionName]]
     *
     * @return [[@doctodo return_type:getBaseActionName]] [[@doctodo return_description:getBaseActionName]]
     */
    protected static function getBaseActionName($actionName)
    {
        $parts = explode(':', $actionName);

        return $parts[0];
    }

    /**
     * [[@doctodo method_description:createAccess]].
     *
     * @param [[@doctodo param_type:acl]] $acl    [[@doctodo param_description:acl]]
     * @param array                       $config [[@doctodo param_description:config]] [optional]
     *
     * @return [[@doctodo return_type:createAccess]] [[@doctodo return_description:createAccess]]
     */
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

    /**
     * [[@doctodo method_description:findNullAction]].
     *
     * @param [[@doctodo param_type:acls]] $acls [[@doctodo param_description:acls]]
     *
     * @return [[@doctodo return_type:findNullAction]] [[@doctodo return_description:findNullAction]]
     */
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

    /**
     * [[@doctodo method_description:canGeneral]].
     *
     * @param [[@doctodo param_type:action]]          $action          [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:model]]           $model           [[@doctodo param_description:model]]
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:canGeneral]] [[@doctodo return_description:canGeneral]]
     */
    public function canGeneral($action, $model, $accessingObject = null)
    {
        if (!is_object($model)) {
            $model = new $model();
        }

        return !$model->getBehavior('Access') || $model->can($action, $accessingObject);
    }

    /**
     * Get controlled object.
     *
     * @param [[@doctodo param_type:object]]     $object     [[@doctodo param_description:object]]
     * @param [[@doctodo param_type:modelClass]] $modelClass [[@doctodo param_description:modelClass]] [optional]
     *
     * @return [[@doctodo return_type:getControlledObject]] [[@doctodo return_description:getControlledObject]]
     */
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

            return [$object->primaryKey];
        }

        return false;
    }

    /**
     * [[@doctodo method_description:buildInnerRoleCheckConditions]].
     *
     * @param [[@doctodo param_type:innerOnConditions]] $innerOnConditions [[@doctodo param_description:innerOnConditions]]
     * @param [[@doctodo param_type:innerAlias]]        $innerAlias        [[@doctodo param_description:innerAlias]]
     * @param [[@doctodo param_type:query]]             $query             [[@doctodo param_description:query]]
     *
     * @return [[@doctodo return_type:buildInnerRoleCheckConditions]] [[@doctodo return_description:buildInnerRoleCheckConditions]]
     */
    public function buildInnerRoleCheckConditions(&$innerOnConditions, $innerAlias, $query)
    {
        return true;
    }

    // this function is not possible because it loses inheritance from object types two levels up
    /**
     * [[@doctodo method_description:BADgenerateAclRoleCheckCriteria]].
     *
     * @param [[@doctodo param_type:query]]            $query            [[@doctodo param_description:query]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:modelClass]]       $modelClass       [[@doctodo param_description:modelClass]] [optional]
     * @param array                                    $bannedRoles      [[@doctodo param_description:bannedRoles]] [optional]
     * @param boolean                                  $expandAros       [[@doctodo param_description:expandAros]] [optional]
     *
     * @return [[@doctodo return_type:BADgenerateAclRoleCheckCriteria]] [[@doctodo return_description:BADgenerateAclRoleCheckCriteria]]
     */
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
                if (preg_match('/^' . preg_quote($modelPrefix . '-') . '/', $objectId) !== 1) {
                    $orderControlledObject[] = $objectId;
                }
            }
        }
        $subquery = new Query();
        $innerAlias = 'inner_acl_role';
        $innerOnConditions = ['or'];
        $innerOnConditions[] = ['[[' . $innerAlias . ']].[[controlled_object_id]]' => $controlledObject];
        $innerOnConditions[] = '[[' . $innerAlias . ']].[[controlled_object_id]] = {{' . $query->primaryAlias . '}}.[[' . $query->primaryTablePk . ']]';
        $this->buildInnerRoleCheckConditions($innerOnConditions, $innerAlias, $query);

        if (!empty($bannedRoles)) {
            $innerOnConditions = ['and', $innerOnConditions, ['and', ['not', ['[[' . $innerAlias . ']].[[role_id]]' => $bannedRoles]]]];
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
            $where[] = ['{{' . $aclRoleTable . '}}.[[accessing_object_id]]' => $aroIn];
        } else {
            $where[] = ['{{' . $aclRoleTable . '}}.[[accessing_object_id]]' => null]; //never!
        }

        // $subquery->where($innerOnConditions)->select(['[[role_id]]']);
        if (!empty($orderControlledObject)) {
            $subquery->orderBy(['IF([[controlled_object_id]] IN ("' . implode('", "', $orderControlledObject) . '"), 1, 0)' => SORT_ASC]);
        }
        // $subquery->limit = '1';
        $subquery->where($where);
        $query->leftJoin([$innerAlias => $subquery], $innerOnConditions);
        $query->groupBy('{{' . $query->primaryAlias . '}}.[[' . $query->primaryTablePk . ']]');
        $query->having(['and', '[[accessRoleCheck]] IS NOT NULL']);
        if (!isset($query->ensureSelect)) {
            $query->ensureSelect = [];
        }
        $query->ensureSelect[] = '{{inner_acl_role}}.[[role_id]] as accessRoleCheck';

        return $query;
    }

    /**
     * [[@doctodo method_description:generateAclCheckCriteria]].
     *
     * @param [[@doctodo param_type:query]]            $query              [[@doctodo param_description:query]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject   [[@doctodo param_description:controlledObject]]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject    [[@doctodo param_description:accessingObject]] [optional]
     * @param boolean                                  $allowParentInherit [[@doctodo param_description:allowParentInherit]] [optional]
     * @param [[@doctodo param_type:modelClass]]       $modelClass         [[@doctodo param_description:modelClass]] [optional]
     * @param boolean                                  $expandAros         [[@doctodo param_description:expandAros]] [optional]
     * @param boolean                                  $limitAccess        [[@doctodo param_description:limitAccess]] [optional]
     *
     * @return [[@doctodo return_type:generateAclCheckCriteria]] [[@doctodo return_description:generateAclCheckCriteria]]
     */
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

        $aclOrder[$alias . '.access'] = SORT_ASC;

        $aclOrder['IF(' . $alias . '.accessing_object_id IS NULL, 0, 1)'] = SORT_DESC;
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
            $aclOnConditions[] = ['or', [$alias . '.accessing_object_id' => $aroIn], [$alias . '.accessing_object_id' => null]];
        } else {
            $aclOnConditions[] = [$alias . '.accessing_object_id' => null];
        }

        $aclOrder['IF(' . $alias . '.aca_id IS NULL, 0, 1)'] = SORT_DESC;
        $aclOrder['IF(' . $alias . '.controlled_object_id IS NULL, 0, 1)'] = SORT_DESC;

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
            if (is_object($controlledObject)) {
                $controlledObject = $controlledObject->primaryKey;
            }
            $innerOnConditions[] = [$alias . '.controlled_object_id' => $controlledObject];
        }

        $innerOnConditions[] = $alias . '.controlled_object_id =' . $query->primaryAlias . '.' . $query->primaryTablePk;
        $innerOnConditions[] = [$alias . '.controlled_object_id' => null];

        $aclOnConditions[] = $innerOnConditions;

        $aclClass = Yii::$app->classes['Acl'];

        if ($this->isAclQuery($query)) {
            if (!empty($aclConditions)) {
                $aclOnConditions[] = $aclConditions;
            }
            $query->andWhere($aclOnConditions);
        } else {
            $query->join('INNER JOIN', $aclClass::tableName() . ' ' . $alias . ' USE INDEX(`aclComboAcaAccess`)', $aclOnConditions);
            $query->andWhere($aclConditions);
            $query->groupBy($query->primaryAlias . '.' . $query->primaryTablePk);
        }
        if (!isset($query->orderBy)) {
            $query->orderBy($aclOrder);
        } else {
            $query->orderBy = array_merge($aclOrder, $query->orderBy);
        }

        return $query;
    }

    /**
     * [[@doctodo method_description:isAclQuery]].
     *
     * @param yii\db\Query $query [[@doctodo param_description:query]]
     *
     * @return [[@doctodo return_type:isAclQuery]] [[@doctodo return_description:isAclQuery]]
     */
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

    /**
     * Get general access.
     *
     * @param [[@doctodo param_type:model]]           $model           [[@doctodo param_description:model]]
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:getGeneralAccess]] [[@doctodo return_description:getGeneralAccess]]
     */
    public function getGeneralAccess($model, $accessingObject = null)
    {
        return [];
    }

    /**
     * Get parent action translations.
     *
     * @return [[@doctodo return_type:getParentActionTranslations]] [[@doctodo return_description:getParentActionTranslations]]
     */
    public function getParentActionTranslations()
    {
        return [
            $this->getActionObjectByName('delete')->primaryKey => $this->getActionObjectByName('update'),
        ];
    }

    /**
     * [[@doctodo method_description:translateParentAction]].
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]]
     *
     * @return [[@doctodo return_type:translateParentAction]] [[@doctodo return_description:translateParentAction]]
     */
    public function translateParentAction($object, $action)
    {
        $translationMap = $this->getParentActionTranslations();
        if (isset($translationMap[$action->primaryKey])) {
            return $translationMap[$action->primaryKey];
        } else {
            return $action;
        }
    }

    /**
     * Get access.
     *
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:acaIds]]           $acaIds           [[@doctodo param_description:acaIds]] [optional]
     * @param boolean                                  $expandAros       [[@doctodo param_description:expandAros]] [optional]
     *
     * @return [[@doctodo return_type:getAccess]] [[@doctodo return_description:getAccess]]
     */
    public function getAccess($controlledObject, $accessingObject = null, $acaIds = null, $expandAros = true)
    {
        if (is_null($accessingObject) && !$this->primaryRequestor) {
            return [];
        }
        if (is_null($acaIds)) {
            $acaIds = true;
        }
        if (empty($acaIds)) {
            return [];
        }

        $aclKey = [
            __CLASS__ . '.' . __FUNCTION__,
            is_object($controlledObject) ? $controlledObject->primaryKey : $controlledObject,
            is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject,
            is_object($acaIds) ? $acaIds->primaryKey : $acaIds,
            $expandAros,
            !empty($this->primaryRequestor) ? $this->primaryRequestor->primaryKey : null,
        ];
        $access = Cacher::get($aclKey);
        if ($access) {
            return $access;
        }

        $subquery = new Query();
        $aclClass = Yii::$app->classes['Acl'];
        $alias = $aclClass::tableName();
        $subquery->from = [$aclClass::tableName() . ' ' . $alias];
        $this->generateAclCheckCriteria($subquery, $controlledObject, $accessingObject, true, get_class($controlledObject), $expandAros, false);
        if ($acaIds !== true) {
            $subquery->andWhere(['or', [$alias . '.[[aca_id]]' => $acaIds], [$alias . '.[[aca_id]]' => null]]);
        }

        $query = new Query();
        $query->from(['internal' => $subquery]);
        $query->groupBy('[[internal]].[[aca_id]]');
        $raw = $query->all();
        $results = $this->fillActions($raw, [], $controlledObject, $acaIds);

        Cacher::set($aclKey, $results, 0, $this->aclCacheDependency);

        return $results;
    }

    /**
     * [[@doctodo method_description:clearCanCache]].
     *
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     */
    public function clearCanCache($controlledObject, $accessingObject = null)
    {
        // @todo mix this in with the caching solution
        $this->_objectCanCache = [];
    }

    /**
     * Get primary requestor.
     *
     * @return [[@doctodo return_type:getPrimaryRequestor]] [[@doctodo return_description:getPrimaryRequestor]]
     */
    public function getPrimaryRequestor()
    {
        if ($this->proxy) {
            return $this->proxy;
        }
        if (isset(Yii::$app->user) && Yii::$app->user->isAnonymous) {
            return $this->getPublicGroup();
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

    /**
     * Get top requestors.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:getTopRequestors]] [[@doctodo return_description:getTopRequestors]]
     */
    public function getTopRequestors($accessingObject = null)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        if (is_object($accessingObject)) {
            $arosKey = md5(json_encode([__CLASS__ . '.' . __FUNCTION__, $accessingObject->primaryKey]));
        } else {
            $arosKey = md5(json_encode([__CLASS__ . '.' . __FUNCTION__, false]));
        }

        if (!isset($this->_requestors[$arosKey])) {
            $this->_requestors[$arosKey] = [];
            if ($this->authority && ($requestors = $this->authority->getTopRequestors($accessingObject)) && $requestors) {
                $this->_requestors[$arosKey] = array_merge($this->_requestors[$arosKey], $requestors);
            }
        }

        return array_unique($this->_requestors[$arosKey]);
    }

    /**
     * Get requestors.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:getRequestors]] [[@doctodo return_description:getRequestors]]
     */
    public function getRequestors($accessingObject = null)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        if (is_object($accessingObject)) {
            $arosKey = md5(json_encode([__CLASS__ . '.' . __FUNCTION__, $accessingObject->primaryKey]));
        } else {
            $arosKey = md5(json_encode([__CLASS__ . '.' . __FUNCTION__, false]));
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

    /**
     * [[@doctodo method_description:accessorHasGroup]].
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]]
     * @param [[@doctodo param_type:groupSystemId]]   $groupSystemId   [[@doctodo param_description:groupSystemId]]
     *
     * @return [[@doctodo return_type:accessorHasGroup]] [[@doctodo return_description:accessorHasGroup]]
     */
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

    /**
     * Get accessor groups.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]]
     *
     * @return [[@doctodo return_type:getAccessorGroups]] [[@doctodo return_description:getAccessorGroups]]
     */
    public function getAccessorGroups($accessingObject)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        $groupClass = Yii::$app->classes['Group'];
        $accessingObjectKey = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
        $cacheKey = Cacher::key([__FUNCTION__, $accessingObjectKey], true);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = $accessingObject->parents($groupClass, [], ['disableAccessCheck' => true]);
        }

        return self::$_cache[$cacheKey];
    }

    /**
     * Get accessing object.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]]
     *
     * @return [[@doctodo return_type:getAccessingObject]] [[@doctodo return_description:getAccessingObject]]
     */
    public function getAccessingObject($accessingObject)
    {
        if (is_null($accessingObject)) {
            $accessingObject = $this->primaryRequestor;
        }
        if ($accessingObject && !is_object($accessingObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $accessingObject = $registryClass::getObject($accessingObject, false);
        }

        return $accessingObject;
    }

    /**
     * Get groups.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     * @param boolean                                 $flatten         [[@doctodo param_description:flatten]] [optional]
     *
     * @return [[@doctodo return_type:getGroups]] [[@doctodo return_description:getGroups]]
     */
    public function getGroups($accessingObject = null, $flatten = false)
    {
        $accessingObject = $this->getAccessingObject($accessingObject);
        $accessingObjectKey = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
        $requestKey = md5(json_encode([__FUNCTION__, func_get_args(), $accessingObjectKey]));

        if (!isset(self::$_cache[$requestKey])) {
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

    /**
     * Get action object by name.
     *
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:getActionObjectByName]] [[@doctodo return_description:getActionObjectByName]]
     *
     */
    public function getActionObjectByName($action)
    {
        if (is_object($action)) {
            return $action;
        }
        $actions = $this->getActionsByName();
        if (!isset($actions[$action])) {
            $acaClass = Yii::$app->classes['Aca'];
            $this->_actionsByName[$action] = $acaClass::find()->where(['name' => $action])->one();
            if (empty($this->_actionsByName[$action])) {
                $this->_actionsByName[$action] = new $acaClass();
                $this->_actionsByName[$action]->name = $action;
                if (!$this->_actionsByName[$action]->save()) {
                    throw new Exception("Unable to start new action ($action)!");
                }
                $this->_actionsById[$this->_actionsByName[$action]->id] = $this->_actionsByName[$action];
            }
        }

        return $this->_actionsByName[$action];
    }

    /**
     * [[@doctodo method_description:_getActions]].
     *
     * @return [[@doctodo return_type:_getActions]] [[@doctodo return_description:_getActions]]
     */
    protected function _getActions()
    {
        $acaClass = Yii::$app->classes['Aca'];
        $actions = $acaClass::find()->all();
        $this->_actionsByName = ArrayHelper::index($actions, 'name');
        $this->_actionsById = ArrayHelper::index($actions, 'id');

        return true;
    }

    /**
     * Get actions by name.
     *
     * @return [[@doctodo return_type:getActionsByName]] [[@doctodo return_description:getActionsByName]]
     */
    public function getActionsByName()
    {
        if (is_null($this->_actionsByName)) {
            $this->_getActions();
        }

        return $this->_actionsByName;
    }

    /**
     * [[@doctodo method_description:clearActionsCache]].
     */
    public function clearActionsCache()
    {
        $this->_actionsByName = null;
        $this->_actionsById = null;
    }

    /**
     * Get actions by.
     *
     * @return [[@doctodo return_type:getActionsById]] [[@doctodo return_description:getActionsById]]
     */
    public function getActionsById()
    {
        if (is_null($this->_actionsById)) {
            $this->_getActions();
        }

        return $this->_actionsById;
    }

    /**
     * Get public group.
     *
     * @return [[@doctodo return_type:getPublicGroup]] [[@doctodo return_description:getPublicGroup]]
     */
    public function getPublicGroup()
    {
        return $this->getGroup('public');
    }

    /**
     * Get top group.
     *
     * @return [[@doctodo return_type:getTopGroup]] [[@doctodo return_description:getTopGroup]]
     */
    public function getTopGroup()
    {
        return $this->getGroup('top');
    }

    /**
     * Get group.
     *
     * @param [[@doctodo param_type:systemName]] $systemName  [[@doctodo param_description:systemName]]
     * @param boolean                            $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:getGroup]] [[@doctodo return_description:getGroup]]
     */
    public function getGroup($systemName, $checkAccess = false)
    {
        $groupClass = Yii::$app->classes['Group'];

        return $groupClass::getBySystemName($systemName, $checkAccess);
    }

    /**
     * [[@doctodo method_description:clearExplicitRules]].
     *
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]]
     * @param boolean                                  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:clearExplicitRules]] [[@doctodo return_description:clearExplicitRules]]
     */
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

    /**
     * [[@doctodo method_description:allow]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:allow]] [[@doctodo return_description:allow]]
     */
    public function allow($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 1, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:clear]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:clear]] [[@doctodo return_description:clear]]
     */
    public function clear($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, false, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireDirectAdmin]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:requireDirectAdmin]] [[@doctodo return_description:requireDirectAdmin]]
     */
    public function requireDirectAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -1, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireAdmin]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:requireAdmin]] [[@doctodo return_description:requireAdmin]]
     */
    public function requireAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -2, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireSuperAdmin]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:requireSuperAdmin]] [[@doctodo return_description:requireSuperAdmin]]
     */
    public function requireSuperAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -3, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:parentAccess]].
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @return [[@doctodo return_type:parentAccess]] [[@doctodo return_description:parentAccess]]
     */
    public function parentAccess($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 0, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * Set access.
     *
     * @param [[@doctodo param_type:action]]           $action           [[@doctodo param_description:action]]
     * @param [[@doctodo param_type:access]]           $access           [[@doctodo param_description:access]]
     * @param [[@doctodo param_type:controlledObject]] $controlledObject [[@doctodo param_description:controlledObject]] [optional]
     * @param [[@doctodo param_type:accessingObject]]  $accessingObject  [[@doctodo param_description:accessingObject]] [optional]
     * @param [[@doctodo param_type:aclRole]]          $aclRole          [[@doctodo param_description:aclRole]] [optional]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:setAccess]] [[@doctodo return_description:setAccess]]
     *
     */
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
        if ($access === false) {
            if (!empty($acl) && !$acl->isNewRecord) {
                return $acl->delete();
            } else {
                return true;
            }
        } else {
            if (empty($acl)) {
                $acl = new $aclClass();
                $acl->attributes = $fields;
            }
            if ($acl->isNewRecord || $acl->access != $access || $acl->acl_role_id !== $aclRole) {
                $acl->access = $access;
                $acl->acl_role_id = $aclRole;

                return $acl->save();
            }
        }

        return true;
    }

    /**
     * Get object access.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @return [[@doctodo return_type:getObjectAccess]] [[@doctodo return_description:getObjectAccess]]
     */
    public function getObjectAccess($object)
    {
        $objectAccessClass = $this->objectAccessClass;

        return $objectAccessClass::get($object);
    }

    /**
     * Get object aros.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @return [[@doctodo return_type:getObjectAros]] [[@doctodo return_description:getObjectAros]]
     */
    public function getObjectAros($object)
    {
        $aclClass = Yii::$app->classes['Acl'];
        $where = [];
        $where = ['controlled_object_id' => $this->getControlledObject($object)];
        $aros = $aclClass::find()->where($where)->groupBy(['[[accessing_object_id]]'])->select(['[[accessing_object_id]]'])->asArray()->all();
        $aros = ArrayHelper::getColumn($aros, 'accessing_object_id');

        return $aros;
    }

    /**
     * Get object inherited roles.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     * @param array                          $params [[@doctodo param_description:params]] [optional]
     *
     * @return [[@doctodo return_type:getObjectInheritedRoles]] [[@doctodo return_description:getObjectInheritedRoles]]
     */
    public function getObjectInheritedRoles($object, $params = [])
    {
        if (!isset($params['ignoreControlled'])) {
            $params['ignoreControlled'] = [];
        }
        $params['ignoreControlled'][] = $object->primaryKey;

        return $this->getObjectRoles($object, $params);
    }

    /**
     * Get object roles.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     * @param array                          $params [[@doctodo param_description:params]] [optional]
     *
     * @return [[@doctodo return_type:getObjectRoles]] [[@doctodo return_description:getObjectRoles]]
     */
    public function getObjectRoles($object, $params = [])
    {
        $aclRoleClass = Yii::$app->classes['AclRole'];
        $where = [];
        $controlledObject = $this->getControlledObject($object, get_class($object), ['debug' => true]);
        if (is_object($controlledObject)) {
            $controlledObject = [$controlledObject->primaryKey];
        }
        $topControlledObject = $controlledObject[0];

        if (isset($params['ignoreControlled'])) {
            $controlledObject = array_diff($controlledObject, $params['ignoreControlled']);
        }
        $where = ['controlled_object_id' => $controlledObject];
        $arosRaw = $aclRoleClass::find()->where($where)->select(['[[id]]', '[[controlled_object_id]]', '[[accessing_object_id]]', '[[role_id]]'])->asArray();
        $arosRaw = $arosRaw->all();
        $aros = [];
        foreach ($arosRaw as $aro) {
            if (isset($aros[$aro['accessing_object_id']])) {
                continue;
            }
            $aros[$aro['accessing_object_id']] = [
                'acl_role_id' => $aro['id'],
                'role_id' => $aro['role_id'],
                'inherited' => $topControlledObject !== $aro['controlled_object_id'],
            ];
        }
        ArrayHelper::map($aros, 'accessing_object_id', 'role_id');

        return $aros;
    }

    /**
     * Get top access.
     *
     * @param array $baseAccess [[@doctodo param_description:baseAccess]] [optional]
     *
     * @return [[@doctodo return_type:getTopAccess]] [[@doctodo return_description:getTopAccess]]
     */
    protected function getTopAccess($baseAccess = [])
    {
        $aclClass = Yii::$app->classes['Acl'];
        $base = $aclClass::find()->where(['[[accessing_object_id]]' => null, '[[controlled_object_id]]' => null])->asArray()->all();

        return $this->fillActions($base, $baseAccess);
    }
}
