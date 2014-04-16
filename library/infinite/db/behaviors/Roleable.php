<?php
namespace infinite\db\behaviors;

use Yii;

use infinite\base\Exception;
use infinite\helpers\ArrayHelper;
use infinite\caching\Cacher;

class Roleable extends \infinite\db\behaviors\ActiveRecord
{
    public $accessRoleCheck;
    public $roleableEnabled = true;
    protected $_role = [];
    protected $_roleCurrent = [];
    protected $_roleChanged = [];
    protected static $_cache = [];

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function safeAttributes()
    {
        return ['role', 'roles', 'accessRoleCheck'];
    }

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

    public function setRoles($roles)
    {
        foreach ($roles as $aro => $role) {
            if (is_numeric($aro)) {
                continue;
            }
            $this->setRole($role, $aro);
        }
    }

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

    public function getObjectRoles()
    {
        $cacheKey = json_encode([__FUNCTION__, 'owner' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = Yii::$app->gk->getObjectRoles($this->owner);
        }

        return self::$_cache[$cacheKey];
    }

    public function getObjectInheritedRoles()
    {
        $cacheKey = json_encode([__FUNCTION__, 'owner' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = Yii::$app->gk->getObjectInheritedRoles($this->owner);
        }

        return self::$_cache[$cacheKey];
    }

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

    public function getFirstAroByRole($role)
    {
        $aros = $this->getAroByRole($role);
        if (!empty($aros)) {
            return array_pop($aros);
        }

        return false;
    }

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
        if ($changed) {
            Cacher::invalidateGroup('acl_role');
        }

        return $this->ensureRoleAccess($aclRole, $existing);
    }

    public function determineAccessLevel($role, $aro = null)
    {
        return false;
    }

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

    public function clearAroRole($aro)
    {
        $aclRole = $this->getRole($aro, false);
        if ($aclRole) {
            $aclRole->delete();
        }

        return true;
    }

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
