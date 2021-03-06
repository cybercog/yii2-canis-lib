<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\security;

use Yii;

/**
 * Access [[@doctodo class_description:canis\security\Access]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Access extends \canis\base\Object
{
    /**
     * @var [[@doctodo var_type:_aclModel]] [[@doctodo var_description:_aclModel]]
     */
    protected $_aclModel;
    /**
     * @var [[@doctodo var_type:_accessLevel]] [[@doctodo var_description:_accessLevel]]
     */
    protected $_accessLevel;
    /**
     * @var [[@doctodo var_type:_action]] [[@doctodo var_description:_action]]
     */
    protected $_action;
    /**
     * @var [[@doctodo var_type:_tempCache]] [[@doctodo var_description:_tempCache]]
     */
    protected $_tempCache = [];

    const ACCESS_NONE = 0x10;           // null
    const ACCESS_DIRECT_ADMIN = 0x11;    // -1
    const ACCESS_ADMIN = 0x12;          // -2
    const ACCESS_SUPER_ADMIN = 0x13;    // -3
    const ACCESS_PARENT = 0x30;         // 0
    const ACCESS_GRANTED = 0x20;        // 1

    /**
     * Prepares object for serialization.
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
     */
    public function __sleep()
    {
        if (is_object($this->_aclModel)) {
            $this->_aclModel = $this->_aclModel->primaryKey;
        }

        if (is_object($this->_action)) {
            $this->_action = $this->_action->primaryKey;
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
     * [[@doctodo method_description:can]].
     *
     * @param [[@doctodo param_type:object]]          $object          [[@doctodo param_description:object]]
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]] [optional]
     *
     * @return [[@doctodo return_type:can]] [[@doctodo return_description:can]]
     */
    public function can($object, $accessingObject = null)
    {
        $cacheKey = [__FUNCTION__];
        $cacheKey[] = is_object($object) ? $object->primaryKey : $object;
        $cacheKey[] = is_object($accessingObject) ? $accessingObject->primaryKey : $accessingObject;
        $cacheKey = md5(json_encode($cacheKey));
        if (isset($this->_tempCache[$cacheKey])) {
            return $this->_tempCache[$cacheKey];
        }
        $accessingObject = Yii::$app->gk->getAccessingObject($accessingObject);
        $topRequestors = Yii::$app->gk->getTopRequestors($accessingObject->primaryKey);
        switch ($this->accessLevel) {
            case self::ACCESS_GRANTED:
                return $this->_tempCache[$cacheKey] = true;
            break;
            case self::ACCESS_NONE:
                return $this->_tempCache[$cacheKey] = false;
            break;
            case self::ACCESS_PARENT:
                return $this->_tempCache[$cacheKey] = $object->parentCan($this->action, $accessingObject) === self::ACCESS_GRANTED;
            break;
            case self::ACCESS_DIRECT_ADMIN:
                $this->_tempCache[$cacheKey] = false;
                if (Yii::$app->gk->accessorHasGroup($accessingObject, 'administrators')) {
                    $this->_tempCache[$cacheKey] = true;
                } elseif (isset($this->aclModel)
                    && in_array($this->aclModel->accessing_object_id, $topRequestors)) {
                    $this->_tempCache[$cacheKey] = true;
                }

                return $this->_tempCache[$cacheKey];
            break;
            case self::ACCESS_ADMIN:
                $this->_tempCache[$cacheKey] = false;
                if (Yii::$app->gk->accessorHasGroup($accessingObject, 'administrators')) {
                    $this->_tempCache[$cacheKey] = true;
                }

                return $this->_tempCache[$cacheKey];
            break;
            case self::ACCESS_SUPER_ADMIN:
                $this->_tempCache[$cacheKey] = false;
                if (Yii::$app->gk->accessorHasGroup($accessingObject, 'super_administrators')) {
                    $this->_tempCache[$cacheKey] = true;
                }

                return $this->_tempCache[$cacheKey];
            break;

        }

        return false;
    }

    /**
     * Get human access level.
     *
     * @param [[@doctodo param_type:accessLevel]] $accessLevel [[@doctodo param_description:accessLevel]] [optional]
     *
     * @return [[@doctodo return_type:getHumanAccessLevel]] [[@doctodo return_description:getHumanAccessLevel]]
     */
    public function getHumanAccessLevel($accessLevel = null)
    {
        if (is_null($accessLevel)) {
            $accessLevel = $this->accessLevel;
        }
        switch ($accessLevel) {
            case self::ACCESS_GRANTED:
                return 'Access Granted';
            break;
            case self::ACCESS_NONE:
                return 'No Access';
            break;
            case self::ACCESS_PARENT:
                   return 'Inherit Parent Access';
            break;
            case self::ACCESS_DIRECT_ADMIN:
                   return 'Administrators and Direct Accessors';
            break;
            case self::ACCESS_ADMIN:
                   return 'Administrator Access';
            break;
            case self::ACCESS_SUPER_ADMIN:
                   return 'Super Administrator Access';
            break;
        }

        return 'Unknown';
    }

    /**
     * Set access level.
     *
     * @param [[@doctodo param_type:accessLevel]] $accessLevel [[@doctodo param_description:accessLevel]]
     */
    public function setAccessLevel($accessLevel)
    {
        $this->_accessLevel = self::translateTableAccessValue($accessLevel);
    }

    /**
     * Get access level.
     *
     * @return [[@doctodo return_type:getAccessLevel]] [[@doctodo return_description:getAccessLevel]]
     */
    public function getAccessLevel()
    {
        if (is_null($this->_accessLevel)) {
            if (isset($this->aclModel)) {
                $this->accessLevel = $this->aclModel->access;
            }
        }

        return $this->_accessLevel;
    }

    /**
     * Set acl model.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setAclModel($object)
    {
        $this->_aclModel = $object;
        if (is_object($object)) {
            $this->accessLevel = $object->access;
            $this->action = $object->aca_id;
        }
    }

    /**
     * Get acl model.
     *
     * @return [[@doctodo return_type:getAclModel]] [[@doctodo return_description:getAclModel]]
     */
    public function getAclModel()
    {
        if (!is_object($this->_aclModel) && !empty($this->_aclModel)) {
            $aclClass = Yii::$app->classes['Acl'];
            $this->_aclModel = $aclClass::get($this->_aclModel, false);
        }

        return $this->_aclModel;
    }

    /**
     * Set action.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setAction($object)
    {
        $this->_action = $object;
    }

    /**
     * Get action.
     *
     * @return [[@doctodo return_type:getAction]] [[@doctodo return_description:getAction]]
     */
    public function getAction()
    {
        if (!is_object($this->_action)) {
            $actionClass = Yii::$app->classes['Aca'];
            $this->_action = $actionClass::get($this->_action, false);
        }

        return $this->_action;
    }

    /**
     * [[@doctodo method_description:translateTableAccessValue]].
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:translateTableAccessValue]] [[@doctodo return_description:translateTableAccessValue]]
     */
    public static function translateTableAccessValue($value)
    {
        if ($value == 0 || $value == self::ACCESS_PARENT) {
            return self::ACCESS_PARENT;
        } elseif ($value == 1 || $value == self::ACCESS_GRANTED) {
            return self::ACCESS_GRANTED;
        } elseif ($value == -1 || $value == self::ACCESS_DIRECT_ADMIN) {
            return self::ACCESS_DIRECT_ADMIN;
        } elseif ($value == -2 || $value == self::ACCESS_ADMIN) {
            return self::ACCESS_ADMIN;
        } elseif ($value == -3 || $value == self::ACCESS_SUPER_ADMIN) {
            return self::ACCESS_SUPER_ADMIN;
        } else {
            return self::ACCESS_NONE;
        }
    }

    /**
     * [[@doctodo method_description:translateAccessValue]].
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:translateAccessValue]] [[@doctodo return_description:translateAccessValue]]
     */
    public static function translateAccessValue($value)
    {
        if ($value == self::ACCESS_PARENT || $value == 0) {
            return 0;
        } elseif ($value == self::ACCESS_GRANTED || $value == 1) {
            return 1;
        } elseif ($value == self::ACCESS_DIRECT_ADMIN || $value == -1) {
            return -1;
        } elseif ($value == self::ACCESS_ADMIN || $value == -2) {
            return -2;
        } elseif ($value == self::ACCESS_SUPER_ADMIN || $value == -3) {
            return -3;
        } else {
            return $value;
        }
    }
}
