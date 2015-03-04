<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use Yii;

use infinite\base\exceptions\Exception;
use infinite\helpers\ArrayHelper;
use infinite\db\ActiveRecord;
use infinite\db\Query;
use infinite\db\ActiveQuery;
use infinite\caching\Cacher;

/**
 * Gatekeeper [@doctodo write class description for Gatekeeper]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Gatekeeper extends \infinite\base\Component
{
    /**
     * @var __var_proxy_type__ __var_proxy_description__
     */
    public $proxy = false;
    /**
     * @var __var_debug_type__ __var_debug_description__
     */
    public $debug = false;

    /**
     * @var __var__requestors_type__ __var__requestors_description__
     */
    protected $_requestors;
    /**
     * @var __var__actionsById_type__ __var__actionsById_description__
     */
    protected $_actionsById;
    /**
     * @var __var__actionsByName_type__ __var__actionsByName_description__
     */
    protected $_actionsByName;
    /**
     * @var __var__primaryAro_type__ __var__primaryAro_description__
     */
    protected $_primaryAro;

    /**
     * @var __var__cache_type__ __var__cache_description__
     */
    static $_cache = [];

    /**
     * @var __var__objectCanCache_type__ __var__objectCanCache_description__
     */
    protected $_objectCanCache = [];

    /**
     * @var __var_authorityClass_type__ __var_authorityClass_description__
     */
    public $authorityClass = 'infinite\security\Authority';
    /**
     * @var __var_objectAccessClass_type__ __var_objectAccessClass_description__
     */
    public $objectAccessClass = 'infinite\security\ObjectAccess';
    /**
     * @var __var_accessClass_type__ __var_accessClass_description__
     */
    public $accessClass = 'infinite\security\Access';
    /**
     * @var __var__authority_type__ __var__authority_description__
     */
    protected $_authority;

    public function clearCache() {
        static::$_cache = [];
        $this->_primaryAro = null;
        $this->_requestors = null;
        $this->_objectCanCache = [];
    }
    /**
     * Set authority
     * @param __param_authority_type__ $authority __param_authority_description__
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
     * Get authority
     * @return __return_getAuthority_type__ __return_getAuthority_description__
     */
    public function getAuthority()
    {
        return $this->_authority;
    }

    /**
     * Get acl cache dependency
     * @return __return_getAclCacheDependency_type__ __return_getAclCacheDependency_description__
     */
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

    /**
     * __method_canPublic_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param string $action __param_action_description__ [optional]
     * @return __return_canPublic_type__ __return_canPublic_description__
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
     * __method_is_description__
     * @param __param_group_type__ $group __param_group_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_is_type__ __return_is_description__
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
                        $found = true; break;
                    }
                }
                self::$_cache[$requestKey] = $found;
            }
        }

        return self::$_cache[$requestKey];
    }

    /**
     * __method_can_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_can_type__ __return_can_description__
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
     * __method_fillActions_description__
     * @param __param_acls_type__ $acls __param_acls_description__
     * @param array $baseAccess __param_baseAccess_description__ [optional]
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_acaIds_type__ $acaIds __param_acaIds_description__ [optional]
     * @return __return_fillActions_type__ __return_fillActions_description__
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

    /**
     * Get action map
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @return __return_getActionMap_type__ __return_getActionMap_description__
     */
    protected function getActionMap($controlledObject = null)
    {
        return [];
    }

    /**
     * Get action link
     * @param __param_action_type__ $action __param_action_description__
     * @param array $accessMap __param_accessMap_description__ [optional]
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @return __return_getActionLink_type__ __return_getActionLink_description__
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
     * Get base action name
     * @param __param_actionName_type__ $actionName __param_actionName_description__
     * @return __return_getBaseActionName_type__ __return_getBaseActionName_description__
     */
    protected static function getBaseActionName($actionName)
    {
        $parts = explode(':', $actionName);

        return $parts[0];
    }

    /**
     * __method_createAccess_description__
     * @param __param_acl_type__ $acl __param_acl_description__
     * @param array $config __param_config_description__ [optional]
     * @return __return_createAccess_type__ __return_createAccess_description__
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
     * __method_findNullAction_description__
     * @param __param_acls_type__ $acls __param_acls_description__
     * @return __return_findNullAction_type__ __return_findNullAction_description__
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
     * __method_canGeneral_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_model_type__ $model __param_model_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_canGeneral_type__ __return_canGeneral_description__
     */
    public function canGeneral($action, $model, $accessingObject = null)
    {
        if (!is_object($model)) {
            $model = new $model;
        }

        return !$model->getBehavior('Access') || $model->can($action, $accessingObject);
    }

    /**
     * Get controlled object
     * @param __param_object_type__ $object __param_object_description__
     * @param __param_modelClass_type__ $modelClass __param_modelClass_description__ [optional]
     * @return __return_getControlledObject_type__ __return_getControlledObject_description__
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
     * __method_buildInnerRoleCheckConditions_description__
     * @param __param_innerOnConditions_type__ $innerOnConditions __param_innerOnConditions_description__
     * @param __param_innerAlias_type__ $innerAlias __param_innerAlias_description__
     * @param __param_query_type__ $query __param_query_description__
     * @return __return_buildInnerRoleCheckConditions_type__ __return_buildInnerRoleCheckConditions_description__
     */
    public function buildInnerRoleCheckConditions(&$innerOnConditions, $innerAlias, $query)
    {
        return true;
    }

    // this function is not possible because it loses inheritance from object types two levels up
    /**
     * __method_BADgenerateAclRoleCheckCriteria_description__
     * @param __param_query_type__ $query __param_query_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_modelClass_type__ $modelClass __param_modelClass_description__ [optional]
     * @param array $bannedRoles __param_bannedRoles_description__ [optional]
     * @param boolean $expandAros __param_expandAros_description__ [optional]
     * @return __return_BADgenerateAclRoleCheckCriteria_type__ __return_BADgenerateAclRoleCheckCriteria_description__
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

    /**
     * __method_generateAclCheckCriteria_description__
     * @param __param_query_type__ $query __param_query_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param boolean $allowParentInherit __param_allowParentInherit_description__ [optional]
     * @param __param_modelClass_type__ $modelClass __param_modelClass_description__ [optional]
     * @param boolean $expandAros __param_expandAros_description__ [optional]
     * @param boolean $limitAccess __param_limitAccess_description__ [optional]
     * @return __return_generateAclCheckCriteria_type__ __return_generateAclCheckCriteria_description__
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
            if (is_object($controlledObject)) {
                $controlledObject = $controlledObject->primaryKey;
            }
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

    /**
     * __method_isAclQuery_description__
     * @param yii\db\Query $query __param_query_description__
     * @return __return_isAclQuery_type__ __return_isAclQuery_description__
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
     * Get general access
     * @param __param_model_type__ $model __param_model_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_getGeneralAccess_type__ __return_getGeneralAccess_description__
     */
    public function getGeneralAccess($model, $accessingObject = null)
    {
        return [];
    }

    /**
     * Get parent action translations
     * @return __return_getParentActionTranslations_type__ __return_getParentActionTranslations_description__
     */
    public function getParentActionTranslations()
    {
        return [
            $this->getActionObjectByName('delete')->primaryKey => $this->getActionObjectByName('update')
        ];
    }

    /**
     * __method_translateParentAction_description__
     * @param __param_object_type__ $object __param_object_description__
     * @param __param_action_type__ $action __param_action_description__
     * @return __return_translateParentAction_type__ __return_translateParentAction_description__
     */
    public function translateParentAction($object,$action)
    {
        $translationMap = $this->getParentActionTranslations();
        if (isset($translationMap[$action->primaryKey])) {
            return $translationMap[$action->primaryKey];
        } else {
            return $action;
        }
    }

    /**
     * Get access
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_acaIds_type__ $acaIds __param_acaIds_description__ [optional]
     * @param boolean $expandAros __param_expandAros_description__ [optional]
     * @return __return_getAccess_type__ __return_getAccess_description__
     */
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



    /**
     * __method_clearCanCache_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     */
    public function clearCanCache($controlledObject, $accessingObject = null)
    {
        // @todo mix this in with the caching solution
        $this->_objectCanCache = [];
    }

    /**
     * Get primary requestor
     * @return __return_getPrimaryRequestor_type__ __return_getPrimaryRequestor_description__
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
     * Get top requestors
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_getTopRequestors_type__ __return_getTopRequestors_description__
     */
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

    /**
     * Get requestors
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_getRequestors_type__ __return_getRequestors_description__
     */
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

    /**
     * __method_accessorHasGroup_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__
     * @param __param_groupSystemId_type__ $groupSystemId __param_groupSystemId_description__
     * @return __return_accessorHasGroup_type__ __return_accessorHasGroup_description__
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
     * Get accessor groups
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__
     * @return __return_getAccessorGroups_type__ __return_getAccessorGroups_description__
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
     * Get accessing object
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__
     * @return __return_getAccessingObject_type__ __return_getAccessingObject_description__
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
     * Get groups
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param boolean $flatten __param_flatten_description__ [optional]
     * @return __return_getGroups_type__ __return_getGroups_description__
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
     * Get action object by name
     * @param __param_action_type__ $action __param_action_description__
     * @return __return_getActionObjectByName_type__ __return_getActionObjectByName_description__
     * @throws Exception __exception_Exception_description__
     */
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

    /**
     * __method__getActions_description__
     * @return __return__getActions_type__ __return__getActions_description__
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
     * Get actions by name
     * @return __return_getActionsByName_type__ __return_getActionsByName_description__
     */
    public function getActionsByName()
    {
        if (is_null($this->_actionsByName)) {
            $this->_getActions();
        }

        return $this->_actionsByName;
    }

    /**
     * __method_clearActionsCache_description__
     */
    public function clearActionsCache()
    {
        $this->_actionsByName = null;
        $this->_actionsById = null;
    }

    /**
     * Get actions by
     * @return __return_getActionsById_type__ __return_getActionsById_description__
     */
    public function getActionsById()
    {
        if (is_null($this->_actionsById)) {
            $this->_getActions();
        }

        return $this->_actionsById;
    }

    /**
     * Get public group
     * @return __return_getPublicGroup_type__ __return_getPublicGroup_description__
     */
    public function getPublicGroup()
    {
        return $this->getGroup('public');
    }

    /**
     * Get top group
     * @return __return_getTopGroup_type__ __return_getTopGroup_description__
     */
    public function getTopGroup()
    {
        return $this->getGroup('top');
    }

    /**
     * Get group
     * @param __param_systemName_type__ $systemName __param_systemName_description__
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     * @return __return_getGroup_type__ __return_getGroup_description__
     */
    public function getGroup($systemName, $checkAccess = false)
    {
        $groupClass = Yii::$app->classes['Group'];

        return $groupClass::getBySystemName($systemName, $checkAccess);
    }

    /**
     * __method_clearExplicitRules_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__
     * @param boolean $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_clearExplicitRules_type__ __return_clearExplicitRules_description__
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
     * __method_allow_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_allow_type__ __return_allow_description__
     */
    public function allow($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 1, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * __method_clear_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_clear_type__ __return_clear_description__
     */
    public function clear($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, false, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * __method_requireDirectAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireDirectAdmin_type__ __return_requireDirectAdmin_description__
     */
    public function requireDirectAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -1, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * __method_requireAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireAdmin_type__ __return_requireAdmin_description__
     */
    public function requireAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -2, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * __method_requireSuperAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireSuperAdmin_type__ __return_requireSuperAdmin_description__
     */
    public function requireSuperAdmin($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, -3, $controlledObject, $accessingObject, $aclRole);
    }

    /**
     * __method_parentAccess_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_parentAccess_type__ __return_parentAccess_description__
     */
    public function parentAccess($action, $controlledObject = null, $accessingObject = null, $aclRole = null)
    {
        return $this->setAccess($action, 0, $controlledObject, $accessingObject, $aclRole);
    }


    /**
     * Set access
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_access_type__ $access __param_access_description__
     * @param __param_controlledObject_type__ $controlledObject __param_controlledObject_description__ [optional]
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_setAccess_type__ __return_setAccess_description__
     * @throws Exception __exception_Exception_description__
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
                $acl = new $aclClass;
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
     * Get object access
     * @param __param_object_type__ $object __param_object_description__
     * @return __return_getObjectAccess_type__ __return_getObjectAccess_description__
     */
    public function getObjectAccess($object)
    {
        $objectAccessClass = $this->objectAccessClass;

        return $objectAccessClass::get($object);
    }

    /**
     * Get object aros
     * @param __param_object_type__ $object __param_object_description__
     * @return __return_getObjectAros_type__ __return_getObjectAros_description__
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
     * Get object inherited roles
     * @param __param_object_type__ $object __param_object_description__
     * @param array $params __param_params_description__ [optional]
     * @return __return_getObjectInheritedRoles_type__ __return_getObjectInheritedRoles_description__
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
     * Get object roles
     * @param __param_object_type__ $object __param_object_description__
     * @param array $params __param_params_description__ [optional]
     * @return __return_getObjectRoles_type__ __return_getObjectRoles_description__
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

    /**
     * Get top access
     * @param array $baseAccess __param_baseAccess_description__ [optional]
     * @return __return_getTopAccess_type__ __return_getTopAccess_description__
     */
    protected function getTopAccess($baseAccess = [])
    {
        $aclClass = Yii::$app->classes['Acl'];
        $base = $aclClass::find()->where(['[[accessing_object_id]]' => null, '[[controlled_object_id]]' => null])->asArray()->all();

        return $this->fillActions($base, $baseAccess);
    }

}
