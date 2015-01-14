<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;
use Yii;
/**
 * QueryArchivable [@doctodo write class description for QueryArchivable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryOwner extends QueryBehavior
{
    public $paramName = '_owner';

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
     * __method_beforeQuery_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_beforeQuery_type__ __return_beforeQuery_description__
     */
    public function beforeQuery($event)
    {
        if (
            !isset($this->owner->model)
            || $this->owner->model->getBehavior('Ownable') === null
            || !$this->owner->model->getBehavior('Ownable')->isEnabled()) {
            return true;
        }
        $aclRoleClass = Yii::$app->classes['AclRole'];
        $fieldName = $aclRoleClass::tableName() .'.accessing_object_id';
        $newWhere = $this->fixOwnerKey($fieldName, $this->owner->where);
        if ($newWhere !== $this->owner->where) {
            $this->owner->where = $newWhere;
            $this->owner->leftJoin($aclRoleClass::tableName(), $aclRoleClass::tableName() .'.controlled_object_id ='. $this->owner->primaryAlias .'.'. $this->owner->primaryTablePk);
        }
        return true;
    }

    public function fixOwnerKey($fieldName, $where)
    {
        $changeKey = function( $array, $old_key, $new_key) {
            if( ! array_key_exists( $old_key, $array ) )
                return $array;

            $keys = array_keys( $array );
            $keys[ array_search( $old_key, $keys ) ] = $new_key;

            return array_combine( $keys, $array );
        };

        foreach ($where as $key => $value) {
            if (is_array($value)) {
                $where[$key] = $this->fixOwnerKey($fieldName, $value);
            } elseif ($key === $this->paramName) {
                $where = $changeKey($where, $key, $fieldName);
                return $this->fixOwnerKey($fieldName, $where);
            } elseif (strpos($value, $this->paramName) !== false) {
                $where[$key] = strtr($value, $this->paramName, $fieldName);
            }
        }
        return $where;
    }
}
