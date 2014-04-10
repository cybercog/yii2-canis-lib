<?php
/**
 * library/db/behaviors/Access.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;
use yii\db\Query as BaseQuery;

class QueryAccess extends Query
{
    protected static $_acceptInherit = false;
    protected $_accessingObject;
    protected $_action;
    public $accessAdded = false;
    protected $_bannedRoles;

    public function events()
    {
        return [
            \infinite\db\Query::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }
    
    public static function allowInherit() {
        self::$_acceptInherit = true;
    }

    public static function denyInherit() {
        self::$_acceptInherit = false;
    }

    public function asUser($userName)
    {
        $user = null;
        if (($testUser = Yii::$app->gk->getUser($userName)) && !empty($testUser)) {
            $user = $testUser;
        }
        return $this->asInternal($user);
    }

    public function asGroup($groupSystemName)
    {
        $group = null;
        if (($testGroup = Yii::$app->gk->getGroup($groupSystemName)) && !empty($testGroup)) {
            $group = $testGroup;
        }
        return $this->asInternal($group);
    }

    public function asInternal($acr)
    {
        $this->accessingObject = $acr;
        return $this->owner;
    }

    public function setAccessingObject($value)
    {
        return $this->_accessingObject = $value;
    }


    public function getAccessingObject()
    {
        return $this->_accessingObject;
    }

    public function aclSummary() {
        $summary = [];
        if (!isset(Yii::$app->gk)) { return $summary; }
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

    public function getQueryAccessModel()
    {
        if ($this->owner instanceof \infinite\db\ActiveQuery) {
            return $this->owner->model;
        }
        return false;
    }

    public function setAction($action)
    {
        $this->_action = $action;
        return $this->owner;
    }

    public function getAction()
    {
        if (is_null($this->_action)) {
            return 'list';
        }
        return $this->_action;
    }

    public function addCheckAccess($aca = null) {
        if (is_null($aca)) {
            $aca = $this->action;
        }
        $query = $this->owner;
        if ($this->owner->accessAdded) { return $query; }
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
            $query->andWhere(['or', [$alias.'.aca_id' => $aca->primaryKey], [$alias.'.aca_id' => null]]);
        }

        Yii::$app->gk->generateAclCheckCriteria($query, false, $this->accessingObject, true, $classAlias);

        return $query;
    }

    public function can($action = null) {
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

    public function canPublic($action = 'read') {
        return Yii::$app->gk->canPublic($this->owner, $action);
    }

    public function beforeQuery($event) {
        $this->addCheckAccess();
        return true;
    }


    public function assignCreationRole() {
        return Yii::$app->gk->assignCreationRole($this->owner);
    }

    public function beforeSave($event) {
        if ($this->owner->isNewRecord) { return; }
        // return true;
        if (!$this->can('update')) {
            $event->isValid = false;
            return false;
        }
    }

    public function afterSave($event) {
        $this->assignCreationRole();
        return true;
    }
}
