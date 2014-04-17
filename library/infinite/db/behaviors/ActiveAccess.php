<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;
use infinite\security\Access;

/**
 * ActiveAccess [@doctodo write class description for ActiveAccess]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ActiveAccess extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var__debug_type__ __var__debug_description__
     */
    protected static $_debug = false;
    // from QueryAccess
    /**
     * @var __var__objectAccess_type__ __var__objectAccess_description__
     */
    protected $_objectAccess;
    protected $_access;
    /**
     * @var __var__acaId_type__ __var__acaId_description__
     */
    protected $_acaId;
    /**
     * @var __var__accessingObject_type__ __var__accessingObject_description__
     */
    protected $_accessingObject;

    /**
     * @var __var__accessMap_type__ __var__accessMap_description__
     */
    /**
     * @var __var__access_type__ __var__access_description__
     */
    /**
     * @var __var__access_type__ __var__access_description__
     */
    protected $_accessMap = [];

    /**
    * @inheritdoc
    **/
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

    /**
     * __method_fillAccessMap_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_ensureAca_type__ $ensureAca __param_ensureAca_description__ [optional]
     */
    public function fillAccessMap($accessingObject = null, $ensureAca = null)
    {
        $allAcas = array_keys(Yii::$app->gk->actionsById);
        if (!is_null($ensureAca)) {
            $aca = is_object($ensureAca) ? $ensureAca->primaryKey : $ensureAca;
            if (!in_array($aca, $allAcas)) {
                Yii::$app->gk->clearActionsCache();
                $allAcas = array_keys(Yii::$app->gk->actionsById);
            }
        }
        $currentAcas = array_keys($this->_accessMap);
        $needAcas = array_diff($allAcas, $currentAcas);
        if (count($allAcas) === count($needAcas)) {
            $needAcas = true;
        }
        $access = Yii::$app->gk->getAccess($this->owner, $accessingObject, $needAcas);
        foreach ($access as $key => $value) {
            $this->_accessMap[$key] = $value;
        }
    }

    /**
     * __method_setAccessDebug_description__
     * @param __param_debug_type__ $debug __param_debug_description__
     */
    public function setAccessDebug($debug)
    {
        self::$_debug = $debug;
        Yii::$app->gk->debug = $debug;
    }

    /**
     * __method_can_description__
     * @param __param_aca_type__ $aca __param_aca_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param boolean $relatedObject __param_relatedObject_description__ [optional]
     * @return __return_can_type__ __return_can_description__
     */
    public function can($aca, $accessingObject = null, $relatedObject = false)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        if (!is_object($aca)) {
            $aca = Yii::$app->gk->getActionObjectByName($aca);
        }
        if (!isset($this->_accessMap[$aca->primaryKey])) {
            $this->fillAccessMap($accessingObject, $aca);
        }
        if (!isset($this->_accessMap[$aca->primaryKey])) {
            return false;
        }
        if (!is_object($this->_accessMap[$aca->primaryKey])) {
            \d($this->_accessMap);exit;
        }
        $results = [true];
        $additionalTest = false;
        if ($relatedObject) {
            $results[] = $relatedObject->can('update', $accessingObject);
            if ($aca->name === 'delete') {
                $additionalTest = $this->canDeleteAssociation($relatedObject);
            }
        }
        $results[] = $additionalTest || $this->_accessMap[$aca->primaryKey]->can($this->owner, $accessingObject);

        return min($results);
    }

    /**
     * __method_canDeleteAssociation_description__
     * @param __param_relatedObject_type__ $relatedObject __param_relatedObject_description__
     * @return __return_canDeleteAssociation_type__ __return_canDeleteAssociation_description__
     */
    public function canDeleteAssociation($relatedObject)
    {
        return isset($relatedObject)
                && $relatedObject->can('update');
    }

    /**
     * __method_canUpdateAssociation_description__
     * @param __param_relatedObject_type__ $relatedObject __param_relatedObject_description__
     * @return __return_canUpdateAssociation_type__ __return_canUpdateAssociation_description__
     */
    public function canUpdateAssociation($relatedObject)
    {
        return isset($relatedObject)
                && $relatedObject->can('update');
    }

    /**
     * __method_parentCan_description__
     * @param __param_aca_type__ $aca __param_aca_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @return __return_parentCan_type__ __return_parentCan_description__
     */
    public function parentCan($aca, $accessingObject = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        if (!is_object($aca)) {
            $aca = Yii::$app->gk->getActionObjectByName($aca);
        }
        $aca = Yii::$app->gk->translateParentAction($this->owner, $aca);
        $parentIds = $this->owner->loadAllParentIds();

        if (Yii::$app->gk->can($aca, $parentIds, $accessingObject)) {
            return Access::ACCESS_GRANTED;
        }

        return Access::ACCESS_NONE;
    }

    /**
     * __method_afterFind_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function afterFind($event)
    {
        if (isset($this->_access) && isset($this->_acaId) && !empty($this->owner->isAccessControlled)) {
            if (is_array($this->_acaId)) {
                foreach ($this->_acaId as $acaId) {
                    $this->_accessMap[$acaId] = Access::translateTableAccessValue($this->_access);
                }
            } else {
                $this->_accessMap[$this->_acaId] = Access::translateTableAccessValue($this->_access);
            }
        }
    }

    /**
     * __method_getObjectAccess_description__
     * @return __return_getObjectAccess_type__ __return_getObjectAccess_description__
     */
    public function getObjectAccess()
    {
        if (!isset($this->_objectAccess)) {
            $this->_objectAccess = Yii::$app->gk->getObjectAccess($this->owner);
        }

        return $this->_objectAccess;
    }

    /**
     * __method_setAccessLevel_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_access_type__ $access __param_access_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_setAccessLevel_type__ __return_setAccessLevel_description__
     */
    public function setAccessLevel($action, $access, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }
        $access = Access::translateAccessValue($access);

        return Yii::$app->gk->setAccess($action, $access, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_allow_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_allow_type__ __return_allow_description__
     */
    public function allow($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->allow($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_parentAccess_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_parentAccess_type__ __return_parentAccess_description__
     */
    public function parentAccess($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->parentAccess($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_clear_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_clear_type__ __return_clear_description__
     */
    public function clear($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->clear($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_requireDirectAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireDirectAdmin_type__ __return_requireDirectAdmin_description__
     */
    public function requireDirectAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireDirectAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_requireAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireAdmin_type__ __return_requireAdmin_description__
     */
    public function requireAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_requireSuperAdmin_description__
     * @param __param_action_type__ $action __param_action_description__
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__ [optional]
     * @param __param_aclRole_type__ $aclRole __param_aclRole_description__ [optional]
     * @return __return_requireSuperAdmin_type__ __return_requireSuperAdmin_description__
     */
    public function requireSuperAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireSuperAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * __method_setAccess_description__
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setAccess($value)
    {
        $this->_access = $value;
    }

    /**
     * __method_setAca_id_description__
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setAca_id($value)
    {
        if (is_null($value)) {
            $this->_acaId = array_keys(Yii::$app->gk->actionsById);
        } else {
            $this->_acaId = $value;
        }
    }

    /**
     * __method_asUser_description__
     * @param __param_userName_type__ $userName __param_userName_description__
     * @return __return_asUser_type__ __return_asUser_description__
     */
    public function asUser($userName)
    {
        $user = null;
        if (($testUser = Yii::$app->gk->getUser($userName)) && !empty($testUser)) {
            $user = $testUser;
        }

        return $this->asInternal($user);
    }

    /**
     * __method_asGroup_description__
     * @param __param_groupSystemName_type__ $groupSystemName __param_groupSystemName_description__
     * @return __return_asGroup_type__ __return_asGroup_description__
     */
    public function asGroup($groupSystemName)
    {
        $group = null;
        if (($testGroup = Yii::$app->gk->getGroup($groupSystemName)) && !empty($testGroup)) {
            $group = $testGroup;
        }

        return $this->asInternal($group);
    }

    /**
     * __method_asInternal_description__
     * @param __param_acr_type__ $acr __param_acr_description__
     * @return __return_asInternal_type__ __return_asInternal_description__
     */
    public function asInternal($acr)
    {
        $this->accessingObject = $acr;

        return $this->owner;
    }

    /**
     * __method_setAccessingObject_description__
     * @param __param_value_type__ $value __param_value_description__
     * @return __return_setAccessingObject_type__ __return_setAccessingObject_description__
     */
    public function setAccessingObject($value)
    {
        return $this->_accessingObject = $value;
    }

    /**
     * __method_getAccessingObject_description__
     * @return __return_getAccessingObject_type__ __return_getAccessingObject_description__
     */
    public function getAccessingObject()
    {
        return $this->_accessingObject;
    }
}
