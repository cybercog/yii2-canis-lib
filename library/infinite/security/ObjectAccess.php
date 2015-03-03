<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use Yii;
use infinite\caching\Cacher;
use infinite\helpers\ArrayHelper;

/**
 * ObjectAccess [@doctodo write class description for ObjectAccess]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ObjectAccess extends \infinite\base\Component
{
    /**
     * @var __var__object_type__ __var__object_description__
     */
    protected $_object;
    /**
     * @var __var__requestors_type__ __var__requestors_description__
     */
    protected $_requestors;
    /**
     * @var __var__roles_type__ __var__roles_description__
     */
    protected $_roles;
    /**
     * @var __var__visibility_type__ __var__visibility_description__
     */
    protected $_visibility;
    /**
     * @var __var__tempCache_type__ __var__tempCache_description__
     */
    protected $_tempCache = [];

    /**
     * Prepares object for serialization.
     * @return __return___sleep_type__ __return___sleep_description__
     */
    public function __sleep()
    {
        if (is_object($this->_object)) {
            $this->_object = $this->_object->primaryKey;
        }

        $keys = array_keys((array) $this);
        $bad = ["\0*\0_tempCache"];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            }
        }

        return $keys;
    }

    /**
     * Get
     * @param __param_object_type__ $object __param_object_description__
     * @return __return_get_type__ __return_get_description__
     */
    public static function get($object)
    {
        $objectId = is_object($object) ? $object->primaryKey : $object;
        $accessKey = [__CLASS__.'.'.__FUNCTION__, $objectId];
        $accessObject = Cacher::get($accessKey);
        if ($accessObject) {
            return $accessObject;
        }
        $accessClass = get_called_class();
        $accessObject = Yii::createObject(['class' => $accessClass, 'object' => $object]);
        Cacher::set($accessKey, $accessObject, 0, Yii::$app->gk->aclCacheDependency);

        return $accessObject;
    }

    /**
     * __method_load_description__
     */
    public function load()
    {
        $this->roles;
        $this->requestors;
    }

    /**
     * __method_save_description__
     * @param __param_data_type__ $data __param_data_description__
     * @return __return_save_type__ __return_save_description__
     */
    public function save($data)
    {
        $currentRoles = $this->getRoleObjects();
        foreach ($data as $requestorId => $roleId) {
            if (isset($currentRoles[$requestorId])) {
                if ($currentRoles[$requestorId]['inherited']
                    && isset($currentRoles[$requestorId]['role'])
                    && !$currentRoles[$requestorId]['role']->inheritedEditable) {
                    unset($data[$requestorId]);
                }
            }
        }
        $validation = $this->validate($data, $currentRoles);
        if (!empty($validation['errors']) || $validation === false) {
            if ($validation === false) {
                $validation = ['errors' => 'Unable to save sharing settings.'];
            }

            return $validation;
        }
        foreach ($data as $requestorId => $roleId) {
            if ($roleId === 'none') {
                $roleId = null;
            }
            $this->object->setRole($roleId, $requestorId);
        }
        if (!$this->object->save()) {
            return ['errors' => 'Unable to save sharing settings.'];
        }

        return true;
    }

    /**
     * __method_fillValidationSettings_description__
     * @param __param_validationSettings_type__ $validationSettings __param_validationSettings_description__
     * @return __return_fillValidationSettings_type__ __return_fillValidationSettings_description__
     */
    protected function fillValidationSettings($validationSettings)
    {
        return $validationSettings;
    }

    /**
     * Get universal max role level
     * @return __return_getUniversalMaxRoleLevel_type__ __return_getUniversalMaxRoleLevel_description__
     */
    public function getUniversalMaxRoleLevel()
    {
        return $this->getAccessorRoleLevel();
    }

    /**
     * __method_validateRole_description__
     * @param __param_role_type__ $role __param_role_description__
     * @param __param_validationSettings_type__ $validationSettings __param_validationSettings_description__
     * @return __return_validateRole_type__ __return_validateRole_description__
     */
    protected function validateRole($role, $validationSettings)
    {
        $package = ['errors' => []];
        if ($role === 'none') {
            return $package;
        }
        if (!is_object($role)) {
            $role = Yii::$app->collectors['roles']->getById($role);
        }
        if (empty($role)) {
            $package['errors'][] = 'Invalid role (role does not exist)';

            return $package;
        }
        if (isset($validationSettings['maxRoleLevel']) && $validationSettings['maxRoleLevel'] !== true) {
            if ($role->level > $validationSettings['maxRoleLevel']) {
                $package['errors'][] = 'Invalid role (role level is too high)';
            }
        }
        if (isset($validationSettings['possibleRoles']) && $validationSettings['possibleRoles'] !== true) {
            if (!in_array($role->object->primaryKey, $validationSettings['possibleRoles'])) {
                $package['errors'][] = 'Invalid role (role not valid for this object)';
            }
        }

        return $package;
    }

    /**
     * __method_validate_description__
     * @param __param_data_type__ $data __param_data_description__
     * @return __return_validate_type__ __return_validate_description__
     */
    protected function validate($data)
    {
        $package = ['errors' => []];

        $specialRequestors = ArrayHelper::index($this->specialRequestors, 'object.primaryKey');

        $defaultValidationSettings = [
            'maxRoleLevel' => $this->getUniversalMaxRoleLevel(),
        ];
        $exclusive = [];
        foreach ($data as $requestor => $role) {
            $validationSettings = $defaultValidationSettings;
            if (isset($specialRequestors[$requestor])) {
                $validationSettings = array_merge($validationSettings, $specialRequestors[$requestor]);
            }
            if (!isset($validationSettings['object'])) {
                $registryClass = Yii::$app->classes['Registry'];
                $validationSettings['object'] = $registryClass::getObject($requestor, false);
            }
            if (empty($validationSettings['object'])) {
                $package['errors'][$requestor] = 'Not a valid object';
                continue;
            }
            if (!empty($role) && $role !== 'none') {
                $role = Yii::$app->collectors['roles']->getById($role);
                if ($role && $role->exclusive) {
                    if (isset($exclusive[$role->object->primaryKey])) {
                        $package['errors'][$requestor] = 'There can not be more than one '. $role->object->name;
                        continue;
                    }
                    $exclusive[$role->object->primaryKey] = true;
                }
            }
            $validationSettings = $this->fillValidationSettings($validationSettings);
            $results = $this->validateRole($role, $validationSettings);
            if (!empty($results['errors'])) {
                $package['errors'][$requestor] = implode('; ', $results['errors']);
            }
        }
        $package['data'] = $data;

        return $package;
    }

    /**
     * Get requestors
     * @return __return_getRequestors_type__ __return_getRequestors_description__
     */
    public function getRequestors()
    {
        if (is_null($this->_requestors)) {
            $this->_requestors = [];
            $aros = Yii::$app->gk->getObjectAros($this->object);

            foreach ($this->specialRequestors as $special => $requestorSettings) {
                if (empty($requestorSettings['object'])) { continue; }
                $requestor = $requestorSettings['object'];
                if (!in_array($requestor->primaryKey, $aros)) {
                    $aros[] = $requestor->primaryKey;
                }
            }
            Yii::$app->gk->debug = true;
            foreach ($aros as $aro) {
                $this->_requestors[$aro] = Yii::$app->gk->getAccess($this->object, $aro, null, false);
            }
            Yii::$app->gk->debug = false;
        }

        return $this->_requestors;
    }

    /**
     * Get roles
     * @return __return_getRoles_type__ __return_getRoles_description__
     */
    public function getRoles()
    {
        if (is_null($this->_roles)) {
            $this->_roles = Yii::$app->gk->getObjectRoles($this->object);
            foreach ($this->specialRequestors as $special => $requestor) {
                if (!is_object($requestor['object'])) {
                    continue;
                }
                if (!array_key_exists($requestor['object']->primaryKey, $this->_roles)) {
                    $this->_roles[$requestor['object']->primaryKey] = $this->getRoleObject($requestor['object']);
                }
            }
        }

        return $this->_roles;
    }

    /**
     * Get role objects
     * @return __return_getRoleObjects_type__ __return_getRoleObjects_description__
     */
    public function getRoleObjects()
    {
        if (!isset($this->_tempCache['roled'])) {
            $this->_tempCache['roled'] = [];
            foreach ($this->roles as $requestorId => $roleSet) {
                $this->_tempCache['roled'][$requestorId] = $this->getRoleObject($requestorId, $roleSet);
            }
        }

        return $this->_tempCache['roled'];
    }

    /**
     * Get role object
     * @param __param_requestorId_type__ $requestorId __param_requestorId_description__
     * @param array $roleSet __param_roleSet_description__ [optional]
     * @return __return_getRoleObject_type__ __return_getRoleObject_description__
     */
    public function getRoleObject($requestorId, $roleSet = [])
    {
        $defaultRoleSet = ['role_id' => null, 'inherited' => false, 'acl_role_id' => null];
        if ($roleSet === 'none') {
            $roleSet = [];
        }
        if (is_string($roleSet)) {
            $roleSet = ['role_id' => $roleSet];
        }
        $roleSet = array_merge($defaultRoleSet, $roleSet);
        $registryClass = Yii::$app->classes['Registry'];
        if (is_object($requestorId)) {
            $object = $requestorId;
        } else {
            $object = $registryClass::getObject($requestorId, true);
        }
        $role = null;
        if (!empty($roleSet['role_id']) && $roleSet['role_id'] !== 'none') {
            $role = Yii::$app->collectors['roles']->getById($roleSet['role_id']);
        }
        $roleSet['object'] = $object;
        $roleSet['role'] = $role;
        unset($roleSet['role_id']);

        return $roleSet;
    }

    /**
     * Get special requestors
     * @return __return_getSpecialRequestors_type__ __return_getSpecialRequestors_description__
     */
    public function getSpecialRequestors()
    {
        return [
            'public' => [
                'object' => Yii::$app->gk->publicGroup,
                'maxRoleLevel' => Yii::$app->params['maxRoleLevels']['public']
            ]
        ];
    }

    /**
     * Set object
     * @param __param_object_type__ $object __param_object_description__
     */
    public function setObject($object)
    {
        $this->_object = $object;
        $this->load();
    }

    /**
     * Get object
     * @return __return_getObject_type__ __return_getObject_description__
     */
    public function getObject()
    {
        if (!is_object($this->_object)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_object = $registryClass::getObject($this->_object, false);
        }

        return $this->_object;
    }

    /**
     * Get role help text
     * @param __param_roleItem_type__ $roleItem __param_roleItem_description__
     * @return __return_getRoleHelpText_type__ __return_getRoleHelpText_description__
     */
    public function getRoleHelpText($roleItem)
    {
        return null;
    }

    /**
     * __method_determineVisibility_description__
     * @return __return_determineVisibility_type__ __return_determineVisibility_description__
     */
    public function determineVisibility()
    {
        $groupClass = Yii::$app->classes['Group'];
        $groupPrefix = $groupClass::modelPrefix();
        $publicGroup = Yii::$app->gk->publicGroup;
        $actions = Yii::$app->gk->actionsByName;
        $readAction = $actions['read'];
        $publicAro = isset($this->requestors[$publicGroup->primaryKey]) ? $this->requestors[$publicGroup->primaryKey] : false;
        if ($publicAro && $publicAro[$readAction->primaryKey] === Access::ACCESS_GRANTED) {
            return 'public';
        }

        foreach ($this->requestors as $aro => $access) {
            if (preg_match('/^'. $groupPrefix .'\-/', $aro) === 0) {
                return 'shared';
            }
        }

        return 'private';
    }

    /**
     * Get possible roles
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_getPossibleRoles_type__ __return_getPossibleRoles_description__
     */
    public function getPossibleRoles($accessingObject = null)
    {
        $accessorRoleLevel = $this->getAccessorRoleLevel($accessingObject);
        $roles = [];
        $nullRole = [];
        $nullRole['id'] = 'none';
        $nullRole['system_id'] = 'none';
        $nullRole['exclusive'] = false;
        $nullRole['item'] = null;
        $nullRole['label'] = 'No Access';
        $nullRole['available'] = true;
        $nullRole['level'] = 0;
        $roles['none'] = $nullRole;
        foreach (Yii::$app->collectors['roles']->getAll() as $roleItem) {
            $roles[$roleItem->id] = $roleItem->package;
            $roles[$roleItem->id]['available'] = $accessorRoleLevel === true || $roleItem->level <= $accessorRoleLevel;
            $roles[$roleItem->id]['helpText'] = $this->getRoleHelpText($roleItem);

        }
        ArrayHelper::multisort($roles, 'level', SORT_ASC);

        return $roles;
    }

    /**
     * Get visibility
     * @return __return_getVisibility_type__ __return_getVisibility_description__
     */
    public function getVisibility()
    {
        if (is_null($this->_visibility)) {
            $this->_visibility = $this->determineVisibility();
        }

        return $this->_visibility;
    }

    /**
     * Get accessor role level
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_getAccessorRoleLevel_type__ __return_getAccessorRoleLevel_description__
     */
    public function getAccessorRoleLevel($accessingObject = null)
    {
        $accessingObject = Yii::$app->gk->getAccessingObject($accessingObject);
        if (Yii::$app->gk->accessorHasGroup($accessingObject, ['administrators', 'super_administrators'])) {
            return true;
        }
        $currentRoles = $this->getRoleObjects();
        ArrayHelper::multisort($currentRoles, 'role.level', SORT_DESC);
        $accessingRequestors = Yii::$app->gk->getRequestors($accessingObject);
        foreach ($currentRoles as $roleObject) {
            if (empty($roleObject['role'])) { continue; }
            $objectType = $roleObject['object']->objectType;
            if ($objectType->getBehavior('Authority') !== null) {
                $test = true;
                if (in_array($roleObject['object']->primaryKey, $accessingRequestors)) {
                    return $roleObject['role']->level;
                }
            }
        }

        return 0;
    }
}
