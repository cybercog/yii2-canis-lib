<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use infinite\caching\Cacher;
use infinite\helpers\ArrayHelper;
use Yii;

/**
 * ObjectAccess [[@doctodo class_description:infinite\security\ObjectAccess]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ObjectAccess extends \infinite\base\Component
{
    /**
     * @var [[@doctodo var_type:_object]] [[@doctodo var_description:_object]]
     */
    protected $_object;
    /**
     * @var [[@doctodo var_type:_requestors]] [[@doctodo var_description:_requestors]]
     */
    protected $_requestors;
    /**
     * @var [[@doctodo var_type:_roles]] [[@doctodo var_description:_roles]]
     */
    protected $_roles;
    /**
     * @var [[@doctodo var_type:_visibility]] [[@doctodo var_description:_visibility]]
     */
    protected $_visibility;
    /**
     * @var [[@doctodo var_type:_tempCache]] [[@doctodo var_description:_tempCache]]
     */
    protected $_tempCache = [];

    /**
     * Prepares object for serialization.
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
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
     * Get.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @return [[@doctodo return_type:get]] [[@doctodo return_description:get]]
     */
    public static function get($object)
    {
        $objectId = is_object($object) ? $object->primaryKey : $object;
        $accessKey = [__CLASS__ . '.' . __FUNCTION__, $objectId];
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
     * [[@doctodo method_description:load]].
     */
    public function load()
    {
        $this->roles;
        $this->requestors;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @param [[@doctodo param_type:data]] $data [[@doctodo param_description:data]]
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
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
     * [[@doctodo method_description:fillValidationSettings]].
     *
     * @param [[@doctodo param_type:validationSettings]] $validationSettings [[@doctodo param_description:validationSettings]]
     *
     * @return [[@doctodo return_type:fillValidationSettings]] [[@doctodo return_description:fillValidationSettings]]
     */
    protected function fillValidationSettings($validationSettings)
    {
        return $validationSettings;
    }

    /**
     * Get universal max role level.
     *
     * @return [[@doctodo return_type:getUniversalMaxRoleLevel]] [[@doctodo return_description:getUniversalMaxRoleLevel]]
     */
    public function getUniversalMaxRoleLevel()
    {
        return $this->getAccessorRoleLevel();
    }

    /**
     * [[@doctodo method_description:validateRole]].
     *
     * @param [[@doctodo param_type:role]]               $role               [[@doctodo param_description:role]]
     * @param [[@doctodo param_type:validationSettings]] $validationSettings [[@doctodo param_description:validationSettings]]
     *
     * @return [[@doctodo return_type:validateRole]] [[@doctodo return_description:validateRole]]
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
     * [[@doctodo method_description:validate]].
     *
     * @param [[@doctodo param_type:data]] $data [[@doctodo param_description:data]]
     *
     * @return [[@doctodo return_type:validate]] [[@doctodo return_description:validate]]
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
                        $package['errors'][$requestor] = 'There can not be more than one ' . $role->object->name;
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
     * Get requestors.
     *
     * @return [[@doctodo return_type:getRequestors]] [[@doctodo return_description:getRequestors]]
     */
    public function getRequestors()
    {
        if (is_null($this->_requestors)) {
            $this->_requestors = [];
            $aros = Yii::$app->gk->getObjectAros($this->object);

            foreach ($this->specialRequestors as $special => $requestorSettings) {
                if (empty($requestorSettings['object'])) {
                    continue;
                }
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
     * Get roles.
     *
     * @return [[@doctodo return_type:getRoles]] [[@doctodo return_description:getRoles]]
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
     * Get role objects.
     *
     * @return [[@doctodo return_type:getRoleObjects]] [[@doctodo return_description:getRoleObjects]]
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
     * Get role object.
     *
     * @param [[@doctodo param_type:requestorId]] $requestorId [[@doctodo param_description:requestorId]]
     * @param array                               $roleSet     [[@doctodo param_description:roleSet]] [optional]
     *
     * @return [[@doctodo return_type:getRoleObject]] [[@doctodo return_description:getRoleObject]]
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
     * Get special requestors.
     *
     * @return [[@doctodo return_type:getSpecialRequestors]] [[@doctodo return_description:getSpecialRequestors]]
     */
    public function getSpecialRequestors()
    {
        return [
            'public' => [
                'object' => Yii::$app->gk->publicGroup,
                'maxRoleLevel' => Yii::$app->params['maxRoleLevels']['public'],
            ],
        ];
    }

    /**
     * Set object.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setObject($object)
    {
        $this->_object = $object;
        $this->load();
    }

    /**
     * Get object.
     *
     * @return [[@doctodo return_type:getObject]] [[@doctodo return_description:getObject]]
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
     * Get role help text.
     *
     * @param [[@doctodo param_type:roleItem]] $roleItem [[@doctodo param_description:roleItem]]
     *
     * @return [[@doctodo return_type:getRoleHelpText]] [[@doctodo return_description:getRoleHelpText]]
     */
    public function getRoleHelpText($roleItem)
    {
        return;
    }

    /**
     * [[@doctodo method_description:determineVisibility]].
     *
     * @return [[@doctodo return_type:determineVisibility]] [[@doctodo return_description:determineVisibility]]
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
            if (preg_match('/^' . $groupPrefix . '\-/', $aro) === 0) {
                return 'shared';
            }
        }

        return 'private';
    }

    /**
     * Get possible roles.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:getPossibleRoles]] [[@doctodo return_description:getPossibleRoles]]
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
     * Get visibility.
     *
     * @return [[@doctodo return_type:getVisibility]] [[@doctodo return_description:getVisibility]]
     */
    public function getVisibility()
    {
        if (is_null($this->_visibility)) {
            $this->_visibility = $this->determineVisibility();
        }

        return $this->_visibility;
    }

    /**
     * Get accessor role level.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:getAccessorRoleLevel]] [[@doctodo return_description:getAccessorRoleLevel]]
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
            if (empty($roleObject['role'])) {
                continue;
            }
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
