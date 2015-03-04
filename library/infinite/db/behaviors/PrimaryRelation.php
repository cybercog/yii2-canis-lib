<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\db\models\Relation;

/**
 * PrimaryRelation [@doctodo write class description for PrimaryRelation].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class PrimaryRelation extends \infinite\db\behaviors\ActiveRecord
{
    /**
     */
    public $primaryChildField = 'primary_child';
    /**
     */
    public $primaryParentField = 'primary_parent';
    /**
     */
    public $wasPrimary = ['parent' => false, 'child' => false];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     *
     */
    public function handlePrimary($role)
    {
        return $this->owner instanceof Relation;
    }

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
     *
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
     *
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
     *
     */
    public function afterUpdate($event = null)
    {
        if (!$this->owner->isActive) {
            $this->handOffPrimary();
        }

        return true;
    }

    /**
     *
     */
    public function afterDelete($event = null)
    {
        $this->handOffPrimary();
    }

    /**
     *
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
