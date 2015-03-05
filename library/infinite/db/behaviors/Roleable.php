<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\base\exceptions\Exception;
use infinite\caching\Cacher;
use infinite\helpers\ArrayHelper;
use Yii;

/**
 * Roleable [[@doctodo class_description:infinite\db\behaviors\Roleable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Roleable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:accessRoleCheck]] [[@doctodo var_description:accessRoleCheck]]
     */
    public $accessRoleCheck;
    /**
     * @var [[@doctodo var_type:roleableEnabled]] [[@doctodo var_description:roleableEnabled]]
     */
    public $roleableEnabled = true;
    /**
     * @var [[@doctodo var_type:_role]] [[@doctodo var_description:_role]]
     */
    protected $_role = [];
    /**
     * @var [[@doctodo var_type:_roleCurrent]] [[@doctodo var_description:_roleCurrent]]
     */
    protected $_roleCurrent = [];
    /**
     * @var [[@doctodo var_type:_roleChanged]] [[@doctodo var_description:_roleChanged]]
     */
    protected $_roleChanged = [];
    /**
     * @var [[@doctodo var_type:_cache]] [[@doctodo var_description:_cache]]
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
     * [[@doctodo method_description:normalizeRole]].
     *
     * @return [[@doctodo return_type:normalizeRole]] [[@doctodo return_description:normalizeRole]]
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
            return;
        }

        return $role;
    }

    /**
     * [[@doctodo method_description:normalizeAro]].
     *
     * @return [[@doctodo return_type:normalizeAro]] [[@doctodo return_description:normalizeAro]]
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
     * Set roles.
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
     * Set role.
     *
     * @param boolean $handle [[@doctodo param_description:handle]] [optional]
     *
     * @return [[@doctodo return_type:setRole]] [[@doctodo return_description:setRole]]
     */
    public function setRole($role, $aro = null, $handle = false)
    {
        $aro = $this->normalizeAro($aro);
        if (empty($aro)) {
            return false;
        }
        $this->_role[$aro] = $role;
        if ($handle) {
            return $this->handleRoleSave();
        }

        return true;
    }

    /**
     * Get object roles.
     *
     * @return [[@doctodo return_type:getObjectRoles]] [[@doctodo return_description:getObjectRoles]]
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
     * Get object inherited roles.
     *
     * @return [[@doctodo return_type:getObjectInheritedRoles]] [[@doctodo return_description:getObjectInheritedRoles]]
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
     * Get role.
     *
     * @param boolean $includeNew [[@doctodo param_description:includeNew]] [optional]
     *
     * @return [[@doctodo return_type:getRole]] [[@doctodo return_description:getRole]]
     */
    public function getRole($aro = null, $includeNew = true)
    {
        $aro = $this->normalizeAro($aro);
        if (empty($aro)) {
            return false;
        }
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
     * Get aro by role.
     *
     * @return [[@doctodo return_type:getAroByRole]] [[@doctodo return_description:getAroByRole]]
     */
    public function getAroByRole($role)
    {
        $role = $this->normalizeRole($role);
        if (!$role->object) {
            return false;
        }
        $cacheKey = json_encode([__FUNCTION__,
            'role' => $role->object->primaryKey,
            'object' => $this->owner->primaryKey, ]);
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
     * Get first aro by role.
     *
     * @return [[@doctodo return_type:getFirstAroByRole]] [[@doctodo return_description:getFirstAroByRole]]
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
     * [[@doctodo method_description:isEnabled]].
     *
     * @return [[@doctodo return_type:isEnabled]] [[@doctodo return_description:isEnabled]]
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
     * [[@doctodo method_description:handleRoleSave]].
     *
     * @return [[@doctodo return_type:handleRoleSave]] [[@doctodo return_description:handleRoleSave]]
     */
    public function handleRoleSave($event = null)
    {
        if (!$this->isEnabled()) {
            return true;
        }
        $current = null;
        foreach ($this->_role as $aroId => $role) {
            //  unset($this->_role[$aroId]);
            $aro = $this->normalizeAro($aroId);
            $role = $this->normalizeRole($role);
            if (empty($aro)) {
                continue;
            }
            if (!$this->internalSetRole($role, $aro)) {
                return false;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:internalSetRole]].
     *
     * @return [[@doctodo return_type:internalSetRole]] [[@doctodo return_description:internalSetRole]]
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
            $aclRole = new $aclRoleClass();
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
     * [[@doctodo method_description:determineAccessLevel]].
     *
     * @return [[@doctodo return_type:determineAccessLevel]] [[@doctodo return_description:determineAccessLevel]]
     */
    public function determineAccessLevel($role, $aro = null)
    {
        return false;
    }

    /**
     * [[@doctodo method_description:ensureRoleAccess]].
     *
     * @param boolean $existing [[@doctodo param_description:existing]] [optional]
     *
     * @return [[@doctodo return_type:ensureRoleAccess]] [[@doctodo return_description:ensureRoleAccess]]
     */
    public function ensureRoleAccess($aclRole, $existing = false)
    {
        $registryClass = Yii::$app->classes['Registry'];
        $aclClass = Yii::$app->classes['Acl'];
        $aro = $registryClass::getObject($aclRole->accessing_object_id, false);
        if (!$aro) {
            return false;
        }

        if (empty($aclRole->role_id)) {
            $this->owner->requireDirectAdmin(null, $aro, $aclRole);

            return true;
        }

        $roleModel = $aclRole->role;
        if (empty($roleModel)) {
            return false;
        }
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
     * Get current roles.
     *
     * @return [[@doctodo return_type:getCurrentRoles]] [[@doctodo return_description:getCurrentRoles]]
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
     * [[@doctodo method_description:clearRoleCache]].
     */
    public function clearRoleCache()
    {
        $this->_roleCurrent = [];
    }

    /**
     * [[@doctodo method_description:clearAroRole]].
     *
     * @return [[@doctodo return_type:clearAroRole]] [[@doctodo return_description:clearAroRole]]
     */
    public function clearAroRole($aro)
    {
        $aclRole = $this->getRole($aro, false);
        if ($aclRole) {
            if (!$aclRole->delete()) {
                \d($aclRole);
                exit;

                return false;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:afterSave]].
     *
     * @throws \ [[@doctodo exception_description:\]]
     * @return [[@doctodo return_type:afterSave]] [[@doctodo return_description:afterSave]]
     *
     */
    public function afterSave($event)
    {
        if (!$this->isEnabled()) {
            return;
        }
        if ($this->owner->getBehavior('Relatable') !== null) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->handleRoleSave($event)) {
            return false;
        }
    }
}
