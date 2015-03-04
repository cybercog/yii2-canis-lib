<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

/**
 * QueryAccess [@doctodo write class description for QueryAccess].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryAccess extends QueryBehavior
{
    /**
     * @var __var__acceptInherit_type__ __var__acceptInherit_description__
     */
    protected static $_acceptInherit = false;
    /**
     * @var __var__accessingObject_type__ __var__accessingObject_description__
     */
    protected $_accessingObject;
    /**
     * @var __var__action_type__ __var__action_description__
     */
    protected $_action;
    /**
     * @var __var_accessAdded_type__ __var_accessAdded_description__
     */
    public $accessAdded = false;
    /**
     * @var __var__bannedRoles_type__ __var__bannedRoles_description__
     */
    protected $_bannedRoles;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\Query::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }

    /**
     * __method_allowInherit_description__.
     */
    public static function allowInherit()
    {
        static::$_acceptInherit = true;
    }

    /**
     * __method_denyInherit_description__.
     */
    public static function denyInherit()
    {
        static::$_acceptInherit = false;
    }

    /**
     * __method_asUser_description__.
     *
     * @param __param_userName_type__ $userName __param_userName_description__
     *
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
     * __method_asGroup_description__.
     *
     * @param __param_groupSystemName_type__ $groupSystemName __param_groupSystemName_description__
     *
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
     * __method_asInternal_description__.
     *
     * @param __param_acr_type__ $acr __param_acr_description__
     *
     * @return __return_asInternal_type__ __return_asInternal_description__
     */
    public function asInternal($acr)
    {
        $this->accessingObject = $acr;

        return $this->owner;
    }

    /**
     * Set accessing object.
     *
     * @param __param_value_type__ $value __param_value_description__
     *
     * @return __return_setAccessingObject_type__ __return_setAccessingObject_description__
     */
    public function setAccessingObject($value)
    {
        return $this->_accessingObject = $value;
    }

    /**
     * Get accessing object.
     *
     * @return __return_getAccessingObject_type__ __return_getAccessingObject_description__
     */
    public function getAccessingObject()
    {
        return $this->_accessingObject;
    }

    /**
     * __method_aclSummary_description__.
     *
     * @return __return_aclSummary_type__ __return_aclSummary_description__
     */
    public function aclSummary()
    {
        $summary = [];
        if (!isset(Yii::$app->gk)) {
            return $summary;
        }
        $access = Yii::$app->gk->getAccess($this->owner);
        $actions = Yii::$app->gk->getActionsById();
        foreach ($actions as $actionId => $action) {
            if (!empty($access[$actionId])) {
                $summary[$action->name] = true;
            } else {
                $summary[$action->name] = false;
            }
        }

        return $summary;
    }

    /**
     * Get query access model.
     *
     * @return __return_getQueryAccessModel_type__ __return_getQueryAccessModel_description__
     */
    public function getQueryAccessModel()
    {
        if ($this->owner instanceof \infinite\db\ActiveQuery) {
            return $this->owner->model;
        }

        return false;
    }

    /**
     * Set action.
     *
     * @param __param_action_type__ $action __param_action_description__
     *
     * @return __return_setAction_type__ __return_setAction_description__
     */
    public function setAction($action)
    {
        $this->_action = $action;

        return $this->owner;
    }

    /**
     * Get action.
     *
     * @return __return_getAction_type__ __return_getAction_description__
     */
    public function getAction()
    {
        if (is_null($this->_action)) {
            return 'list';
        }

        return $this->_action;
    }

    /**
     * __method_addCheckAccess_description__.
     *
     * @param __param_aca_type__ $aca __param_aca_description__ [optional]
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_addCheckAccess_type__ __return_addCheckAccess_description__
     */
    public function addCheckAccess($aca = null)
    {
        if (is_null($aca)) {
            $aca = $this->action;
        }
        $query = $this->owner;
        if ($this->owner->accessAdded) {
            return $query;
        }
        $this->owner->accessAdded = true;
        $parentClass = $this->owner->modelClass;
        $classAlias = $parentClass::modelAlias();
        if ($aca) {
            $aclClass = Yii::$app->classes['Acl'];
            $alias = $aclClass::tableName();
            $aca = Yii::$app->gk->getActionObjectByName($aca);
            if (empty($aca)) {
                throw new Exception("ACL is not set up correctly. No '{$aca}' action!");
            }
            $query->andWhere(['or', [$alias . '.aca_id' => $aca->primaryKey], [$alias . '.aca_id' => null]]);
        }

        Yii::$app->gk->generateAclCheckCriteria($query, false, $this->accessingObject, static::$_acceptInherit, $classAlias);

        return $query;
    }

    /**
     * __method_can_description__.
     *
     * @param __param_action_type__ $action __param_action_description__ [optional]
     *
     * @return __return_can_type__ __return_can_description__
     */
    public function can($action = null)
    {
        if (is_array($action)) {
            foreach ($action as $a) {
                if (!$this->owner->can($a)) {
                    return false;
                }
            }

            return true;
        }

        return Yii::$app->gk->can($action, $this->owner);
    }

    /**
     * __method_canPublic_description__.
     *
     * @param string $action __param_action_description__ [optional]
     *
     * @return __return_canPublic_type__ __return_canPublic_description__
     */
    public function canPublic($action = 'read')
    {
        return Yii::$app->gk->canPublic($this->owner, $action);
    }

    /**
     * __method_beforeQuery_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_beforeQuery_type__ __return_beforeQuery_description__
     */
    public function beforeQuery($event)
    {
        $this->addCheckAccess();

        return true;
    }

    /**
     * __method_assignCreationRole_description__.
     *
     * @return __return_assignCreationRole_type__ __return_assignCreationRole_description__
     */
    public function assignCreationRole()
    {
        return Yii::$app->gk->assignCreationRole($this->owner);
    }

    /**
     * __method_beforeSave_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_beforeSave_type__ __return_beforeSave_description__
     */
    public function beforeSave($event)
    {
        if ($this->owner->isNewRecord) {
            return;
        }
        // return true;
        if (!$this->can('update')) {
            $event->isValid = false;

            return false;
        }
    }

    /**
     * __method_afterSave_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_afterSave_type__ __return_afterSave_description__
     */
    public function afterSave($event)
    {
        $this->assignCreationRole();

        return true;
    }
}
