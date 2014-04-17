<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use Yii;

/**
 * Access [@doctodo write class description for Access]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Access extends \infinite\base\Object
{
    /**
     * @var __var__aclModel_type__ __var__aclModel_description__
     */
    protected $_aclModel;
    /**
     * @var __var__accessLevel_type__ __var__accessLevel_description__
     */
    protected $_accessLevel;
    /**
     * @var __var__action_type__ __var__action_description__
     */
    protected $_action;
    /**
     * @var __var__tempCache_type__ __var__tempCache_description__
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
     * @return __return___sleep_type__ __return___sleep_description__
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
     * __method_can_description__
     * @param __param_object_type__ $object __param_object_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_can_type__ __return_can_description__
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
     * Get human access level
     * @param __param_accessLevel_type__ $accessLevel __param_accessLevel_description__ [optional]
     * @return __return_getHumanAccessLevel_type__ __return_getHumanAccessLevel_description__
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
     * Set access level
     * @param __param_accessLevel_type__ $accessLevel __param_accessLevel_description__
     */
    public function setAccessLevel($accessLevel)
    {
        $this->_accessLevel = self::translateTableAccessValue($accessLevel);
    }

    /**
     * Get access level
     * @return __return_getAccessLevel_type__ __return_getAccessLevel_description__
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
     * Set acl model
     * @param __param_object_type__ $object __param_object_description__
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
     * Get acl model
     * @return __return_getAclModel_type__ __return_getAclModel_description__
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
     * Set action
     * @param __param_object_type__ $object __param_object_description__
     */
    public function setAction($object)
    {
        $this->_action = $object;
    }

    /**
     * Get action
     * @return __return_getAction_type__ __return_getAction_description__
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
     * __method_translateTableAccessValue_description__
     * @param __param_value_type__ $value __param_value_description__
     * @return __return_translateTableAccessValue_type__ __return_translateTableAccessValue_description__
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
     * __method_translateAccessValue_description__
     * @param __param_value_type__ $value __param_value_description__
     * @return __return_translateAccessValue_type__ __return_translateAccessValue_description__
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
