<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors;

use Yii;

/**
 * QueryAccess [[@doctodo class_description:canis\db\behaviors\QueryAccess]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryAccess extends QueryBehavior
{
    /**
     * @var [[@doctodo var_type:_acceptInherit]] [[@doctodo var_description:_acceptInherit]]
     */
    protected static $_acceptInherit = false;
    /**
     * @var [[@doctodo var_type:_accessingObject]] [[@doctodo var_description:_accessingObject]]
     */
    protected $_accessingObject;
    /**
     * @var [[@doctodo var_type:_action]] [[@doctodo var_description:_action]]
     */
    protected $_action;
    /**
     * @var [[@doctodo var_type:accessAdded]] [[@doctodo var_description:accessAdded]]
     */
    public $accessAdded = false;
    /**
     * @var [[@doctodo var_type:_bannedRoles]] [[@doctodo var_description:_bannedRoles]]
     */
    protected $_bannedRoles;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \canis\db\Query::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }

    /**
     * [[@doctodo method_description:allowInherit]].
     */
    public static function allowInherit()
    {
        static::$_acceptInherit = true;
    }

    /**
     * [[@doctodo method_description:denyInherit]].
     */
    public static function denyInherit()
    {
        static::$_acceptInherit = false;
    }

    /**
     * [[@doctodo method_description:asUser]].
     *
     * @param [[@doctodo param_type:userName]] $userName [[@doctodo param_description:userName]]
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
     * @param [[@doctodo param_type:groupSystemName]] $groupSystemName [[@doctodo param_description:groupSystemName]]
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
     * @param [[@doctodo param_type:acr]] $acr [[@doctodo param_description:acr]]
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
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
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

    /**
     * [[@doctodo method_description:aclSummary]].
     *
     * @return [[@doctodo return_type:aclSummary]] [[@doctodo return_description:aclSummary]]
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
     * @return [[@doctodo return_type:getQueryAccessModel]] [[@doctodo return_description:getQueryAccessModel]]
     */
    public function getQueryAccessModel()
    {
        if ($this->owner instanceof \canis\db\ActiveQuery) {
            return $this->owner->model;
        }

        return false;
    }

    /**
     * Set action.
     *
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]]
     *
     * @return [[@doctodo return_type:setAction]] [[@doctodo return_description:setAction]]
     */
    public function setAction($action)
    {
        $this->_action = $action;

        return $this->owner;
    }

    /**
     * Get action.
     *
     * @return [[@doctodo return_type:getAction]] [[@doctodo return_description:getAction]]
     */
    public function getAction()
    {
        if (is_null($this->_action)) {
            return 'list';
        }

        return $this->_action;
    }

    /**
     * [[@doctodo method_description:addCheckAccess]].
     *
     * @param [[@doctodo param_type:aca]] $aca [[@doctodo param_description:aca]] [optional]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:addCheckAccess]] [[@doctodo return_description:addCheckAccess]]
     *
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
     * [[@doctodo method_description:can]].
     *
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]] [optional]
     *
     * @return [[@doctodo return_type:can]] [[@doctodo return_description:can]]
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
     * [[@doctodo method_description:canPublic]].
     *
     * @param string $action [[@doctodo param_description:action]] [optional]
     *
     * @return [[@doctodo return_type:canPublic]] [[@doctodo return_description:canPublic]]
     */
    public function canPublic($action = 'read')
    {
        return Yii::$app->gk->canPublic($this->owner, $action);
    }

    /**
     * [[@doctodo method_description:beforeQuery]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeQuery]] [[@doctodo return_description:beforeQuery]]
     */
    public function beforeQuery($event)
    {
        $this->addCheckAccess();

        return true;
    }

    /**
     * [[@doctodo method_description:assignCreationRole]].
     *
     * @return [[@doctodo return_type:assignCreationRole]] [[@doctodo return_description:assignCreationRole]]
     */
    public function assignCreationRole()
    {
        return Yii::$app->gk->assignCreationRole($this->owner);
    }

    /**
     * [[@doctodo method_description:beforeSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeSave]] [[@doctodo return_description:beforeSave]]
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
     * [[@doctodo method_description:afterSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:afterSave]] [[@doctodo return_description:afterSave]]
     */
    public function afterSave($event)
    {
        $this->assignCreationRole();

        return true;
    }
}
