<?php
/**
 * library/db/behaviors/Access.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;
use yii\db\Query;

class QueryAccess extends \infinite\db\behaviors\ActiveRecord
{
    protected $_aclEnabled = false; //@todo got to work on this behavior
    protected static $_acceptInherit = false;

    public function events()
    {
        return [
            \infinite\db\ActiveQuery::EVENT_BEFORE_QUERY => 'beforeQuery',
        ];
    }


    public function enableAccessCheck() {
        $this->_aclEnabled = true;
        return $this->owner;
    }

    public function disableAccessCheck() {
        $this->_aclEnabled = false;
        return $this->owner;
    }

    public function getIsAclEnabled() {
        if (!isset(Yii::$app->gk)) { return false; }
        return $this->_aclEnabled AND $this->owner->isAco;
    }

    public static function allowInherit() {
        self::$_acceptInherit = true;
    }

    public static function denyInherit() {
        self::$_acceptInherit = false;
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


    public function addCheckNoAccess($aca = 'read') {
        return $this->addCheckAccess($aca, true);
    }

    public function addCheckAccess($aca = 'read', $inverse = false) {
        $query = $this->owner;
        $aclClass = Yii::$app->gk->aclClass;
        $alias = $aclClass::tableName();
        $parentClass = $this->owner->modelClass;
        $classAlias = $parentClass::modelAlias();
        Yii::$app->gk->generateAclCheckCriteria($query, false, null, $classAlias, true);

        if ($aca) {
            $aca = Yii::$app->gk->getActionObjectByName($aca);
            if (empty($aca)) {
                throw new Exception("ACL is not set up correctly. No '{$aca}' action!");
            } 
            $query->andWhere(['or', [$alias.'.aca_id' => $aca->primaryKey], [$alias.'.aca_id' => null]]);
        }
        return $query;
    }

    public function addCheckAccessOld($aca = 'read', $criteria = null, $inverse = false) {
        if (!$this->isAclEnabled) { return $this->owner; }
        \d(get_class(Yii::$app->gk));
        \d(Yii::$app->gk->authority);

        exit;
        if (is_null($criteria)) {
            $baseCriteria = $this->owner->getDbCriteria();
            $criteria = new CDbCriteria;
            $criteria->mergeWith($baseCriteria);
            $baseCriteria = new CDbCriteria;
        }

        $acaOriginal = $aca;
        $alias = 'acl';
        $aclModel = Gatekeeper::ACL_MODEL;
        $aclModel = $aclModel::tempModel();
        // get aro's 
        $aros = Yii::$app->gk->aros;
        // get aca for read
        if ($aca) {
            $aca = Yii::$app->gk->getActionObjectByName($aca);
            if (empty($aca)) {
                throw new RException("ACL is not set up correctly. No '{$aca}' action!");
            }
        }

        $tableAlias = $this->owner->tableAlias;
        $aclOrder = [];
        $aclOnConditions = [];
        $aroN = 0;
        $aroIn = [];
        $aclOrder[] = 'IF('.$alias.'.access IS NULL, 0, 1) DESC';

        $aclOrder[] = 'IF('.$alias.'.accessing_object_id IS NULL, 0, 1) DESC';
        foreach ($aros as $aro) {
            if (is_array($aro)) {
                $subInIf = [];
                foreach ($aro as $sa) {
                    $criteria->params[':aro_'.$aroN] = $sa;
                    $aroIn[] = ':aro_'.$aroN;
                    $subInIf[] = ':aro_'.$aroN;
                    $aroN++;
                }
                $aclOrder[] = 'IF('.$alias.'.accessing_object_id IN ('.implode(', ', $subInIf).'), 1, 0) DESC';
            } else {
                $criteria->params[':aro_'.$aroN] = $aro;
                $aroIn[] = ':aro_'.$aroN;
                $aclOrder[] = 'IF('.$alias.'.accessing_object_id = :aro_'.$aroN.', 1, 0) DESC';
                $aroN++;
            }
        }
        
        if (!empty($aroIn)) {
            $aclOnConditions[] = ''.$alias.'.accessing_object_id IN ('.implode(', ', $aroIn).') OR '.$alias.'.accessing_object_id IS NULL';
        } else {
            $aclOnConditions[] = ''.$alias.'.accessing_object_id IS NULL';
        }
        
        if ($inverse) {
            $aclConditions = ''.$alias.'.access = -1';
        } else {
            $aclConditions = ''.$alias.'.access = 1';

            if (self::$_acceptInherit) {
                $aclConditions .= ' OR '.$alias.'.access = 0';
            }
        }
        $criteria->params[':object_model'] = $this->owner->modelAlias;
        if ($acaOriginal) {
            $criteria->params[':aca_id'] = $aca->primaryKey;
            $aclOnConditions[] = ''.$alias.'.aca_id=:aca_id OR '.$alias.'.aca_id IS NULL';
        }

        $aclOrder[] = 'IF('.$alias.'.aca_id IS NULL, 0, 1) DESC';
        $aclOrder[] = 'IF('.$alias.'.controlled_object_id IS NULL, 0, 1) DESC';
        $aclOrder[] = 'IF('.$alias.'.object_model IS NULL, 0, 1) DESC';
        $aclOnConditions[] = ''.$alias.'.controlled_object_id='.$tableAlias.'.id OR ('.$alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model=:object_model) OR ('.$alias.'.controlled_object_id IS NULL AND '.$alias.'.object_model IS NULL)';

        if (isset($aclConditions)) {
            $aclOnConditions[] = $aclConditions;
        }
        $criteria->distinct = true;
        $join = ' INNER JOIN `'.$aclModel->tableName().'` AS '.$alias.' ON (('.implode(') AND (', $aclOnConditions).'))';

        $criteria->mergeWith(['join' => $join, 'order' => implode(', ', $aclOrder)], true);
        //RDebug::d($criteria)
        $this->owner->dbCriteria = new CDbCriteria;
        $this->owner->dbCriteria->mergeWith($criteria);
        return $this->owner;
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
        if (!$this->owner->isAco) { return true; }
        if (!$this->isAclEnabled) { return true; }
        $this->addCheckAccess();
        return true;
    }

    public function beforeCount($event) {
        $this->beforeFind($event);
    }

    public function assignCreationRole() {
        return Yii::$app->gk->assignCreationRole($this->owner);
    }

    public function beforeSave($event) {
        if (!$this->isAclEnabled) { return; }
        if ($this->owner->isNewRecord) { return; }
        // return true;
        if (!$this->can('update')) {
            $event->isValid = false;
            return false;
        }
    }

    public function afterSave($event) {
        if (!$this->isAclEnabled) { return; }
        $this->assignCreationRole();
        return true;
    }
}
