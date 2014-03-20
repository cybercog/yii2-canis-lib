<?php
namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;
use infinite\helpers\ArrayHelper;

class Roleable extends \infinite\db\behaviors\ActiveRecord
{
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
        return ['role', 'roles'];
    }


    public function normalizeRole($role = null)
    {
        if (!is_null($role) && is_string($role)) {
            $roleTest = Yii::$app->collectors['roles']->getOne($role);
            if ($roleTest) {
                $role = $roleTest->object;
            } else {
                $role = false;
            }
        }
        if (is_object($role)) {
            $role = $role->system_id;
        }
        if (empty($role)) {
            return false;
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
        if (!isset($this->_roleChanged[$aro])) {
            $this->_roleChanged[$aro] = false;
        }
        if (isset($this->_role[$aro])) {
            $checkRoleA = is_object($role) ? $role->primaryKey : $role;
            $checkRoleB = is_object($this->_role[$aro]) ? $this->_role[$aro]->primaryKey : $this->_role[$aro];
            $this->_roleChanged[$aro] = $checkRoleB !== $checkRoleA;
        } else {
            $this->_roleChanged[$aro] = true;
        }
        $this->_role[$aro] = $role;
        if ($handle) {
            return $this->handleRoleSave();
        }
        return true;
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
        $cacheKey = json_encode(['role' => $role, 'object' => $this->owner->primaryKey]);
        if (!isset(self::$_cache[$cacheKey])) {
            self::$_cache[$cacheKey] = false;
            $aclRoleClass = Yii::$app->classes['AclRole'];
            $params = [];
            $params['controlled_object_id'] = $this->owner->primaryKey;
            $params['role_id'] = $role;
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

    protected function internalSetRole($role, $aro) {
        $aro = $this->normalizeAro($aro);
        $role = $this->normalizeRole($role);
        $aclRoleClass = Yii::$app->classes['AclRole'];
        if (empty($role)) {
            $this->clearAroRole($aro);
            return true;
        }
        $roleItem = Yii::$app->collectors['roles']->getOne($role);
        if ($roleItem->exclusive) {
            $params = [];
            $params['role_id'] = $roleItem->object->primaryKey;
            $params['controlled_object_id'] = $this->owner->primaryKey;
            $currentAros = $aclRoleClass::find()->where($params)->all();
            foreach ($currentAros as $aclRole) {
                if (!$this->internalSetRole($roleItem->conflictRole, $aclRole->accessing_object_id)) {
                    return false;
                }
            }
        }
        $aclRole = $this->getRole($aro, false);
        if (!$aclRole) {
            $aclRole = new $aclRoleClass;
        }
        $params = [];
        $params['controlled_object_id'] = $this->owner->primaryKey;
        $params['accessing_object_id'] = $aro;
        $params['role_id'] = $roleItem->object->primaryKey;
        $aclRole->attributes = $params;
        if (!$aclRole->save()) {
            return false;
        }
        return $this->ensureRoleAccess($aclRole);
    }

    public function determineAccessLevel($role, $aro = null)
    {
        return false;
    }

    public function ensureRoleAccess($aclRole)
    {
        $registryClass = Yii::$app->classes['Registry'];
        $roleModel = $aclRole->role;
        if (empty($roleModel)) { return false; }
        $role = Yii::$app->collectors['roles']->getOne($roleModel->system_id);
        if (empty($role) || empty($role->object->primaryKey)) {
            return false;
        }
        $role = $role->object->system_id;
        $aro = $registryClass::getObject($aclRole->accessing_object_id, false);
        if (!$aro) { return false; }
        $accessLevels = $this->owner->determineAccessLevel($role, $aro);
        if ($accessLevels === false) {
            Yii::$app->gk->clearExplicitRules($this->owner->primaryKey, $aro);
            return true;
        } else {
            foreach ($accessLevels as $key => $action) {
                if (is_numeric($key)) {
                    $this->owner->allow($action, $aro, $aclRole);
                } else {
                    $accessLevel = $action;
                    $action = $key;
                    $this->owner->setAccessLevel($action, $accessLevel, $aro, $aclRole);
                }
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
