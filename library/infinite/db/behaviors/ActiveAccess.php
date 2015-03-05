<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\security\Access;
use Yii;

/**
 * ActiveAccess [[@doctodo class_description:infinite\db\behaviors\ActiveAccess]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveAccess extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:_debug]] [[@doctodo var_description:_debug]]
     */
    protected static $_debug = false;
    /**
     * @var [[@doctodo var_type:_objectAccess]] [[@doctodo var_description:_objectAccess]]
     */
    protected $_objectAccess;
    /**
     * @var [[@doctodo var_type:_access]] [[@doctodo var_description:_access]]
     */
    protected $_access;
    /**
     * @var [[@doctodo var_type:_acaId]] [[@doctodo var_description:_acaId]]
     */
    protected $_acaId;
    /**
     * @var [[@doctodo var_type:_accessingObject]] [[@doctodo var_description:_accessingObject]]
     */
    protected $_accessingObject;
    /**
     * @var [[@doctodo var_type:_accessMap]] [[@doctodo var_description:_accessMap]]
     */
    protected $_accessMap = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
        ];
    }

    /**
     * [[@doctodo method_description:clearActiveAccessCache]].
     */
    public function clearActiveAccessCache()
    {
        $this->_objectAccess = null;
        $this->_access = null;
        $this->_acaId = null;
        $this->_accessingObject = null;
        $this->_accessMap = [];
    }

    /**
     * [[@doctodo method_description:fillAccessMap]].
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
     * Set access debug.
     */
    public function setAccessDebug($debug)
    {
        self::$_debug = $debug;
        Yii::$app->gk->debug = $debug;
    }

    /**
     * [[@doctodo method_description:can]].
     *
     * @param boolean $relatedObject [[@doctodo param_description:relatedObject]] [optional]
     *
     * @return [[@doctodo return_type:can]] [[@doctodo return_description:can]]
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
            \d($this->_accessMap);
            exit;
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
     * [[@doctodo method_description:canDeleteAssociation]].
     *
     * @return [[@doctodo return_type:canDeleteAssociation]] [[@doctodo return_description:canDeleteAssociation]]
     */
    public function canDeleteAssociation($relatedObject)
    {
        return isset($relatedObject)
                && $relatedObject->can('update');
    }

    /**
     * [[@doctodo method_description:canUpdateAssociation]].
     *
     * @return [[@doctodo return_type:canUpdateAssociation]] [[@doctodo return_description:canUpdateAssociation]]
     */
    public function canUpdateAssociation($relatedObject)
    {
        return isset($relatedObject)
                && $relatedObject->can('update');
    }

    /**
     * [[@doctodo method_description:parentCan]].
     *
     * @return [[@doctodo return_type:parentCan]] [[@doctodo return_description:parentCan]]
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
     * [[@doctodo method_description:afterFind]].
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
     * Get object access.
     *
     * @return [[@doctodo return_type:getObjectAccess]] [[@doctodo return_description:getObjectAccess]]
     */
    public function getObjectAccess()
    {
        if (!isset($this->_objectAccess)) {
            $this->_objectAccess = Yii::$app->gk->getObjectAccess($this->owner);
        }

        return $this->_objectAccess;
    }

    /**
     * Set access level.
     *
     * @return [[@doctodo return_type:setAccessLevel]] [[@doctodo return_description:setAccessLevel]]
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
     * [[@doctodo method_description:allow]].
     *
     * @return [[@doctodo return_type:allow]] [[@doctodo return_description:allow]]
     */
    public function allow($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->allow($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:parentAccess]].
     *
     * @return [[@doctodo return_type:parentAccess]] [[@doctodo return_description:parentAccess]]
     */
    public function parentAccess($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->parentAccess($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:clear]].
     *
     * @return [[@doctodo return_type:clear]] [[@doctodo return_description:clear]]
     */
    public function clear($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->clear($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireDirectAdmin]].
     *
     * @return [[@doctodo return_type:requireDirectAdmin]] [[@doctodo return_description:requireDirectAdmin]]
     */
    public function requireDirectAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireDirectAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireAdmin]].
     *
     * @return [[@doctodo return_type:requireAdmin]] [[@doctodo return_description:requireAdmin]]
     */
    public function requireAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * [[@doctodo method_description:requireSuperAdmin]].
     *
     * @return [[@doctodo return_type:requireSuperAdmin]] [[@doctodo return_description:requireSuperAdmin]]
     */
    public function requireSuperAdmin($action, $accessingObject = null, $aclRole = null)
    {
        if (is_null($accessingObject) && !is_null($this->accessingObject)) {
            $accessingObject = $this->accessingObject;
        }

        return Yii::$app->gk->requireSuperAdmin($action, $this->owner, $accessingObject, $aclRole);
    }

    /**
     * Set access.
     */
    public function setAccess($value)
    {
        $this->_access = $value;
    }

    /**
     * Set aca.
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
     * [[@doctodo method_description:asUser]].
     *
     * @return [[@doctodo return_type:asUser]] [[@doctodo return_description:asUser]]
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
     * [[@doctodo method_description:asGroup]].
     *
     * @return [[@doctodo return_type:asGroup]] [[@doctodo return_description:asGroup]]
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
     * [[@doctodo method_description:asInternal]].
     *
     * @return [[@doctodo return_type:asInternal]] [[@doctodo return_description:asInternal]]
     */
    public function asInternal($acr)
    {
        $this->accessingObject = $acr;

        return $this->owner;
    }

    /**
     * Set accessing object.
     *
     * @return [[@doctodo return_type:setAccessingObject]] [[@doctodo return_description:setAccessingObject]]
     */
    public function setAccessingObject($value)
    {
        return $this->_accessingObject = $value;
    }

    /**
     * Get accessing object.
     *
     * @return [[@doctodo return_type:getAccessingObject]] [[@doctodo return_description:getAccessingObject]]
     */
    public function getAccessingObject()
    {
        return $this->_accessingObject;
    }
}
