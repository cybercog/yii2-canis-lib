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
     * @var __var_primaryField_type__ __var_primaryField_description__
     */
    public $primaryChildField = 'primary_child';
    /**
     * @var __var_primaryField_type__ __var_primaryField_description__
     */
    public $primaryParentField = 'primary_parent';
    /**
     * @var __var_wasPrimary_type__ __var_wasPrimary_description__
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
     * __method_handlePrimary_description__.
     *
     * @return __return_handlePrimary_type__ __return_handlePrimary_description__
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
     *
     * @param boolean $primaryOnly __param_primaryOnly_description__ [optional]
     *
     * @return __return_getSiblings_type__ __return_getSiblings_description__
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
            $relationFields['{{%alias%}}.[['.$primaryField.']]'] = 1;
        }

        return $childObject->siblingRelationQuery($parentObject, ['where' => $relationFields], ['disableAccess' => true])->all();
    }

    /**
     * __method_beforeInsert_description__.
     *
     * @param __param_event_type__ $event __param_event_description__ [optional]
     *
     * @return __return_beforeInsert_type__ __return_beforeInsert_description__
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
     * __method_beforeUpdate_description__.
     *
     * @param __param_event_type__ $event __param_event_description__ [optional]
     *
     * @return __return_beforeUpdate_type__ __return_beforeUpdate_description__
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
     * __method_afterUpdate_description__.
     *
     * @param __param_event_type__ $event __param_event_description__ [optional]
     *
     * @return __return_afterUpdate_type__ __return_afterUpdate_description__
     */
    public function afterUpdate($event = null)
    {
        if (!$this->owner->isActive) {
            $this->handOffPrimary();
        }

        return true;
    }

    /**
     * __method_afterDelete_description__.
     *
     * @param __param_event_type__ $event __param_event_description__ [optional]
     *
     * @return __return_afterDelete_type__ __return_afterDelete_description__
     */
    public function afterDelete($event = null)
    {
        $this->handOffPrimary();
    }

    /**
     * __method_handOffPrimary_description__.
     *
     * @return __return_handOffPrimary_type__ __return_handOffPrimary_description__
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
     * @return __return_setPrimary_type__ __return_setPrimary_description__
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
     * @return __return_getIsPrimary_type__ __return_getIsPrimary_description__
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
