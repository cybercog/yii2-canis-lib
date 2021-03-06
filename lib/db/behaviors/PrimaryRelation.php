<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors;

use canis\db\models\Relation;

/**
 * PrimaryRelation [[@doctodo class_description:canis\db\behaviors\PrimaryRelation]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class PrimaryRelation extends \canis\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:primaryChildField]] [[@doctodo var_description:primaryChildField]]
     */
    public $primaryChildField = 'primary_child';
    /**
     * @var [[@doctodo var_type:primaryParentField]] [[@doctodo var_description:primaryParentField]]
     */
    public $primaryParentField = 'primary_parent';
    /**
     * @var [[@doctodo var_type:wasPrimary]] [[@doctodo var_description:wasPrimary]]
     */
    public $wasPrimary = ['parent' => false, 'child' => false];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \canis\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \canis\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            \canis\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            \canis\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * [[@doctodo method_description:handlePrimary]].
     *
     * @param [[@doctodo param_type:role]] $role [[@doctodo param_description:role]]
     *
     * @return [[@doctodo return_type:handlePrimary]] [[@doctodo return_description:handlePrimary]]
     */
    public function handlePrimary($role)
    {
        return $this->owner instanceof Relation;
    }

    /**
     * Get primary field.
     *
     * @param [[@doctodo param_type:role]] $role [[@doctodo param_description:role]]
     *
     * @return [[@doctodo return_type:getPrimaryField]] [[@doctodo return_description:getPrimaryField]]
     */
    public function getPrimaryField($role)
    {
        if (in_array($role, ['child', 'children'])) {
            return $this->primaryChildField;
        } else {
            return $this->primaryParentField;
        }
    }

    /**
     * Get siblings.
     *
     * @param [[@doctodo param_type:role]] $role        [[@doctodo param_description:role]]
     * @param boolean                      $primaryOnly [[@doctodo param_description:primaryOnly]] [optional]
     *
     * @return [[@doctodo return_type:getSiblings]] [[@doctodo return_description:getSiblings]]
     */
    public function getSiblings($role, $primaryOnly = false)
    {
        $primaryField = $this->getPrimaryField($role);
        $parentObject = $this->owner->parentObject;
        $childObject = $this->owner->childObject;
        if (empty($childObject)) {
            return [];
        }
        $relationFields = [];
        if ($primaryOnly) {
            $relationFields['{{%alias%}}.[[' . $primaryField . ']]'] = 1;
        }

        return $childObject->siblingRelationQuery($parentObject, ['where' => $relationFields], ['disableAccess' => true])->all();
    }

    /**
     * [[@doctodo method_description:beforeInsert]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     *
     * @return [[@doctodo return_type:beforeInsert]] [[@doctodo return_description:beforeInsert]]
     */
    public function beforeInsert($event = null)
    {
        foreach (['child', 'parent'] as $role) {
            if (!$this->handlePrimary($role)) {
                continue;
            }
            $primaryField = $this->getPrimaryField($role);
            $primarySiblings = $this->getSiblings($role, true);

            $this->wasPrimary[$role] = !empty($this->owner->{$primaryField});
            if (!$this->owner->isActive) {
                $this->owner->{$primaryField} = 0;
            } elseif (empty($primarySiblings)) {
                $this->owner->{$primaryField} = 1;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:beforeUpdate]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     *
     * @return [[@doctodo return_type:beforeUpdate]] [[@doctodo return_description:beforeUpdate]]
     */
    public function beforeUpdate($event = null)
    {
        foreach (['child', 'parent'] as $role) {
            $primaryField = $this->getPrimaryField($role);
            $this->wasPrimary[$role] = !empty($this->owner->{$primaryField});
            if (!$this->owner->isActive) {
                $this->owner->{$primaryField} = 0;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:afterUpdate]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     *
     * @return [[@doctodo return_type:afterUpdate]] [[@doctodo return_description:afterUpdate]]
     */
    public function afterUpdate($event = null)
    {
        if (!$this->owner->isActive) {
            $this->handOffPrimary();
        }

        return true;
    }

    /**
     * [[@doctodo method_description:afterDelete]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     */
    public function afterDelete($event = null)
    {
        $this->handOffPrimary();
    }

    /**
     * [[@doctodo method_description:handOffPrimary]].
     *
     * @return [[@doctodo return_type:handOffPrimary]] [[@doctodo return_description:handOffPrimary]]
     */
    public function handOffPrimary()
    {
        foreach (['child', 'parent'] as $role) {
            if (!$this->handlePrimary($role)) {
                continue;
            }
            if ($this->owner->isPrimary($role) || $this->owner->wasPrimary[$role]) {
                // assign a new primary
                $siblings = $this->getSiblings($role, false);
                if (!empty($siblings)) {
                    $sibling = array_shift($siblings);
                    $sibling->setPrimary($role);
                }
            }
        }

        return true;
    }

    /**
     * Set primary.
     *
     * @param [[@doctodo param_type:role]] $role [[@doctodo param_description:role]]
     *
     * @return [[@doctodo return_type:setPrimary]] [[@doctodo return_description:setPrimary]]
     */
    public function setPrimary($role)
    {
        if (!$this->handlePrimary($role)) {
            return false;
        }
        $primaryField = $this->getPrimaryField($role);
        $primarySiblings = $this->getSiblings($role, true);
        foreach ($primarySiblings as $sibling) {
            $sibling->{$primaryField} = 0;
            if (!$sibling->save()) {
                return false;
            }
        }
        $this->owner->{$primaryField} = 1;

        return $this->owner->save();
    }

    /**
     * Get is primary.
     *
     * @param [[@doctodo param_type:role]] $role [[@doctodo param_description:role]]
     *
     * @return [[@doctodo return_type:isPrimary]] [[@doctodo return_description:isPrimary]]
     */
    public function isPrimary($role)
    {
        if (!$this->handlePrimary($role)) {
            return false;
        }
        $primaryField = $this->getPrimaryField($role);

        return !empty($this->owner->{$primaryField});
    }
}
