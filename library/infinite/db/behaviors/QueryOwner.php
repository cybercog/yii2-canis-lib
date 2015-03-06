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
 * QueryOwner [[@doctodo class_description:infinite\db\behaviors\QueryOwner]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class QueryOwner extends QueryBehavior
{
    /**
     * @var [[@doctodo var_type:paramName]] [[@doctodo var_description:paramName]]
     */
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
     * [[@doctodo method_description:beforeQuery]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeQuery]] [[@doctodo return_description:beforeQuery]]
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
        $fieldName = $aclRoleClass::tableName() . '.accessing_object_id';
        $newWhere = $this->fixOwnerKey($fieldName, $this->owner->where);
        if ($newWhere !== $this->owner->where) {
            $this->owner->where = $newWhere;
            $this->owner->leftJoin($aclRoleClass::tableName(), $aclRoleClass::tableName() . '.controlled_object_id =' . $this->owner->primaryAlias . '.' . $this->owner->primaryTablePk);
        }

        return true;
    }

    /**
     * [[@doctodo method_description:fixOwnerKey]].
     *
     * @param [[@doctodo param_type:fieldName]] $fieldName [[@doctodo param_description:fieldName]]
     * @param [[@doctodo param_type:where]]     $where     [[@doctodo param_description:where]]
     *
     * @return [[@doctodo return_type:fixOwnerKey]] [[@doctodo return_description:fixOwnerKey]]
     */
    public function fixOwnerKey($fieldName, $where)
    {
        $changeKey = function ($array, $old_key, $new_key) {
            if (! array_key_exists($old_key, $array)) {
                return $array;
            }

            $keys = array_keys($array);
            $keys[ array_search($old_key, $keys) ] = $new_key;

            return array_combine($keys, $array);
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
