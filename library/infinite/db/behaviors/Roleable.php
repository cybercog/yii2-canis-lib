<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

use infinite\base\exceptions\Exception;
use infinite\helpers\ArrayHelper;
use infinite\caching\Cacher;

/**
 * Roleable [@doctodo write class description for Roleable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Roleable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var_accessRoleCheck_type__ __var_accessRoleCheck_description__
     */
    public $accessRoleCheck;
    /**
     * @var __var_roleableEnabled_type__ __var_roleableEnabled_description__
     */
    public $roleableEnabled = true;
    /**
     * @var __var__role_type__ __var__role_description__
     */
    protected $_role = [];
    /**
     * @var __var__roleCurrent_type__ __var__roleCurrent_description__
     */
    protected $_roleCurrent = [];
    /**
     * @var __var__roleChanged_type__ __var__roleChanged_description__
     */
    protected $_roleChanged = [];
    /**
     * @var __var__cache_type__ __var__cache_description__
     */
    protected static $_cache = [];

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
    * @inheritdoc
     */
    public function safeAttributes()
    {
        return ['role', 'roles', 'accessRoleCheck'];
    }

    /**
     * __method_normalizeRole_description__
     * @param __param_role_type__ $role __param_role_description__ [optional]
     * @return __return_normalizeRole_type__ __return_normalizeRole_description__
     */
    public function normalizeRole($role = null)
    {
        if (!is_null($role) && is_string($role)) {
            $roleTest = Yii::$app->collectors['roles']->getById($role);
            $role = null;
            if ($roleTest) {
                $role = $roleTest;
            }
        }
        if (!is_object($role) && is_array($role)) {
            $roleLookup = $role;
            $role = null;
            if (isset($roleLookup['system_id'])) {
                 $roleTest = Yii::$app->collectors['roles']->getOne($roleLookup['system_id']);
                if ($roleTest) {
                    $role = $roleTest;
                }
            }
        }
        if (empty($role)) {
            return null;
        }

        return $role;
    }

    /**
     * __method_normalizeAro_description__
     * @param __param_aro_type__ $aro __param_aro_description__ [optional]
     * @return __return_normalizeAro_type__ __return_normalizeAro_description__
     */
    public function normalizeAro($aro = null)
    {
        if (is_null($aro)) {
            if (isset(Yii::$app->user->identity->primaryKey)) {
                $aro = Yii::$app->user->identity->primaryKey;
            }
        }
        if (is_object($aro)) {
            $aro = $aro->primaryKey;
        }

        return $aro;
    }

    /**
     * Set roles
     * @param __param_roles_type__ $roles __param_roles_description__
     */
    public function setRoles($roles)
    {
        foreach ($roles as $aro => $role) {
            if (is_numeric($aro)) {
                continue;
            }
            $this->setRole($role, $aro);
        }
    }

    /**
     * Set role
     * @param __param_role_type__ $role __param_role_description__
     * @param __param_aro_type__ $aro __param_aro_description__ [optional]
     * @param boolean $handle __param_handle_description__ [optional]
     * @return __return_setRole_type__ __return_setRole_description__
     */
    public function setRole($role, $aro = null, $handle = false)
    {
        $aro = $this->normalizeAro($aro);
        if (empty($aro)) { return false; }
        $this->_role[$aro] = $role;
        if ($handle) {
            return $this->handleRoleSave();
        }

        return true;
    }

    /**
     * Get object roles
     * @return __return_getObjectRoles_type__ __return_getObjectRoles_description__
     */
    public function getObjectRoles()
    {
        $cacheKey = json_encode([__FUNCTION__, 'owner' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = Yii::$app->gk->getObjectRoles($this->owner);
        }

        return self::$_cache[$cacheKey];
    }

    /**
     * Get object inherited roles
     * @return __return_getObjectInheritedRoles_type__ __return_getObjectInheritedRoles_description__
     */
    public function getObjectInheritedRoles()
    {
        $cacheKey = json_encode([__FUNCTION__, 'owner' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = Yii::$app->gk->getObjectInheritedRoles($this->owner);
        }

        return self::$_cache[$cacheKey];
    }

    /**
     * Get role
     * @param __param_aro_type__ $aro __param_aro_description__ [optional]
     * @param boolean $includeNew __param_includeNew_description__ [optional]
     * @return __return_getRole_type__ __return_getRole_description__
     */
    public function getRole($aro = null, $includeNew = true)
    {
        $aro = $this->normalizeAro($aro);
        if (empty($aro)) { return false; }
        if (!isset($this->_role[$aro]) || !$includeNew) {
            if (!isset($this->_roleCurrent[$aro])) {
                $this->_roleCurrent[$aro] = false;
                $aclRoleClass = Yii::$app->classes['AclRole'];
                $params = [];
                $params['controlled_object_id'] = $this->owner->primaryKey;
                $params['accessing_object_id'] = $aro;
                $roleQuery = $aclRoleClass::find()->where($params)->one();
                if ($roleQuery) {
                    $this->_roleCurrent[$aro] = $roleQuery;
                }
            }

            return $this->_roleCurrent[$aro];
        }

        return $this->_role[$aro];
    }

    /**
     * Get aro by role
     * @param __param_role_type__ $role __param_role_description__
     * @return __return_getAroByRole_type__ __return_getAroByRole_description__
     */
    public function getAroByRole($role)
    {
        $role = $this->normalizeRole($role);
        if (!$role->object) { return false; }
        $cacheKey = json_encode([__FUNCTION__,
            'role' => $role->object->primaryKey,
            'object' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = false;
            $aclRoleClass = Yii::$app->classes['AclRole'];
            $params = [];
            $params['controlled_object_id'] = $this->owner->primaryKey;
            $params['role_id'] = $role->object->primaryKey;
            self::$_cache[$cacheKey] = $aclRoleClass::find()->where($params)->all();
            if (empty(self::$_cache[$cacheKey])) {
                self::$_cache[$cacheKey] = false;
            }
        }

        return self::$_cache[$cacheKey];
    }

    /**
     * Get first aro by role
     * @param __param_role_type__ $role __param_role_description__
     * @return __return_getFirstAroByRole_type__ __return_getFirstAroByRole_description__
     */
    public function getFirstAroByRole($role)
    {
        $aros = $this->getAroByRole($role);
        if (!empty($aros)) {
            return array_pop($aros);
        }

        return false;
    }

    /**
     * __method_isEnabled_description__
     * @return __return_isEnabled_type__ __return_isEnabled_description__
     */
    public function isEnabled()
    {
        if ($this->owner->getBehavior('Registry') === null
            || !isset(Yii::$app->collectors['roles'])
            || !$this->owner->roleableEnabled
            || $this->owner->getBehavior('ActiveAccess') === null
            ) {
            return false;
        }

        return true;
    }

    /**
     * __method_handleRoleSave_description__
     * @param __param_event_type__ $event __param_event_description__ [optional]
     * @return __return_handleRoleSave_type__ __return_handleRoleSave_description__
     */
    public function handleRoleSave($event = null)
    {
        if (!$this->isEnabled()) { return true; }
        $current = null;
        foreach ($this->_role as $aroId => $role) {
          //  unset($this->_role[$aroId]);
            $aro = $this->normalizeAro($aroId);
            $role = $this->normalizeRole($role);
            if (empty($aro)) { continue; }
            if (!$this->internalSetRole($role, $aro)) {
                return false;
            }
        }

        return true;
    }

    /**
     * __method_internalSetRole_description__
     * @param __param_role_type__ $role __param_role_description__
     * @param __param_aro_type__ $aro __param_aro_description__
     * @return __return_internalSetRole_type__ __return_internalSetRole_description__
     */
    protected function internalSetRole($role, $aro)
    {
        $aro = $this->normalizeAro($aro);
        $roleItem = $this->normalizeRole($role);
        $roleId = empty($roleItem) ? null : $roleItem->object->primaryKey;
        $aclRoleClass = Yii::$app->classes['AclRole'];
        $gkRoles = $this->getObjectInheritedRoles();
        $inherited = false;
        $inheritedAroRole = false;
        $clearRole = empty($roleItem);
        if (isset($gkRoles[$aro])) {
            $inherited = $gkRoles[$aro]['inherited'];
            if ($inherited) {
                $inheritedAroRoleId = $gkRoles[$aro]['acl_role_id'];
                $inheritedAroRole = $aclRoleClass::get($inheritedAroRoleId);
                $clearRole = $inheritedAroRole->role_id === $roleId;
            }
        }
        if ($clearRole) {
            $this->clearAroRole($aro);
            Cacher::invalidateGroup('acl_role');
            return true;
        }
        $aclRole = $this->getRole($aro, false);
        $existing = true;
        if (!$aclRole) {
            $aclRole = new $aclRoleClass;
            $existing = false;
        }
        $changed = $aclRole->role_id !== $roleId;
        $params = [];
        $params['controlled_object_id'] = $this->owner->primaryKey;
        $params['accessing_object_id'] = $aro;
        $params['role_id'] = $roleId;
        $aclRole->attributes = $params;
        if (!$aclRole->save()) {
            return false;
        }
        $this->_roleCurrent[$aro] = $aclRole;
        if ($changed) {
            Cacher::invalidateGroup('acl_role');
        }

        return $this->ensureRoleAccess($aclRole, $existing);
    }

    /**
     * __method_determineAccessLevel_description__
     * @param __param_role_type__ $role __param_role_description__
     * @param __param_aro_type__ $aro __param_aro_description__ [optional]
     * @return __return_determineAccessLevel_type__ __return_determineAccessLevel_description__
     */
    public function determineAccessLevel($role, $aro = null)
    {
        return false;
    }

    /**
     * __method_ensureRoleAccess_description__
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__
     * @param boolean $existing __param_existing_description__ [optional]
     * @return __return_ensureRoleAccess_type__ __return_ensureRoleAccess_description__
     */
    public function ensureRoleAccess($aclRole, $existing = false)
    {
        $registryClass = Yii::$app->classes['Registry'];
        $aclClass = Yii::$app->classes['Acl'];
        $aro = $registryClass::getObject($aclRole->accessing_object_id, false);
        if (!$aro) { return false; }

        if (empty($aclRole->role_id)) {
            $this->owner->requireDirectAdmin(null, $aro, $aclRole);
            return true;
        }

        $roleModel = $aclRole->role;
        if (empty($roleModel)) { return false; }
        $role = Yii::$app->collectors['roles']->getOne($roleModel->system_id);
        if (empty($role) || empty($role->object->primaryKey)) {
            return false;
        }
        $role = $role->object->system_id;

        $accessLevels = $this->owner->determineAccessLevel($role, $aro);

        $current = [];
        if ($existing) {
            $currentRaw = $aclClass::find()->where(['acl_role_id' => $aclRole->primaryKey])->all();
            $current = ArrayHelper::index($currentRaw, 'aca_id');
        }
        if ($accessLevels === false) {
            Yii::$app->gk->clearExplicitRules($this->owner->primaryKey, $aro);

            return true;
        } else {
            $actionsByName = Yii::$app->gk->getActionsByName();
            foreach ($accessLevels as $key => $action) {
                if (is_numeric($key)) {
                    $this->owner->allow($action, $aro, $aclRole);
                } else {
                    $accessLevel = $action;
                    $action = $key;
                    $this->owner->setAccessLevel($action, $accessLevel, $aro, $aclRole);
                }
                $actionObject = isset($actionsByName[$action]) ? $actionsByName[$action] : false;
                if ($actionObject && isset($current[$actionObject->primaryKey])) {
                   unset($current[$actionObject->primaryKey]);
                }
            }
            foreach ($current as $currentAcl) {
                $currentAcl->delete();
            }
        }

        return true;
    }

    /**
     * Get current roles
     * @return __return_getCurrentRoles_type__ __return_getCurrentRoles_description__
     */
    public function getCurrentRoles()
    {
        if (!isset($this->_currentRoles)) {
            $aclRoleClass = Yii::$app->classes['AclRole'];
            $params = ['controlled_object_id' => $this->owner->primaryKey];
            $this->_currentRoles = $aclRoleClass::find()->where($params)->all();
            $this->_currentRoles = ArrayHelper::index($this->_currentRoles, 'accessing_object_id');
        }

        return $this->_currentRoles;
    }

    /**
     * __method_clearAroRole_description__
     * @param __param_aro_type__ $aro __param_aro_description__
     * @return __return_clearAroRole_type__ __return_clearAroRole_description__
     */
    public function clearAroRole($aro)
    {
        $aclRole = $this->getRole($aro, false);
        if ($aclRole) {
            $aclRole->delete();
        }

        return true;
    }

    /**
     * __method_afterSave_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterSave_type__ __return_afterSave_description__
     * @throws \ __exception_\_description__
     * @throws \ __exception_\_description__
     */
    public function afterSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if ($this->owner->getBehavior('Relatable') !== null) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->handleRoleSave($event)) {
            \d($this);
            throw new \Exception("Unable to save roles!");
        }
    }
}
