<?php
namespace infinite\db\behaviors;

use infinite\db\models\Relation;

class PrimaryRelation extends \infinite\db\behaviors\ActiveRecord
{
    public $primaryField = 'primary';
    public $wasPrimary = false;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    public function handlePrimary()
    {
        return $this->owner instanceof Relation;
    }

    public function getSiblings($primaryOnly = false)
    {
        $parentObject = $this->owner->parentObject;
        $childObject = $this->owner->childObject;
        if (empty($childObject)) { return []; }
        $relationFields = [];
        if ($primaryOnly) {
            $relationFields['%alias%.'. $this->primaryField] = 1;
        }

        return $childObject->siblingRelationQuery($parentObject, ['where' => $relationFields], ['disableAccess' => true])->all();
    }

    public function beforeInsert($event = null)
    {
        if (!$this->handlePrimary()) { return true; }
        $primarySiblings = $this->getSiblings(true);
        $this->wasPrimary = !empty($this->owner->{$this->primaryField});
        if (!$this->owner->isActive) {
            $this->owner->{$this->primaryField} = 0;
        } elseif (empty($primarySiblings)) {
            $this->owner->{$this->primaryField} = 1;
        }

        return true;
    }

    public function beforeUpdate($event = null)
    {
        if (!$this->handlePrimary()) { return true; }
        $this->wasPrimary = !empty($this->owner->{$this->primaryField});
        if (!$this->owner->isActive) {
            $this->owner->{$this->primaryField} = 0;
        }

        return true;
    }

    public function afterUpdate($event = null)
    {
        if (!$this->handlePrimary()) { return true; }
        if (!$this->owner->isActive) {
            $this->handOffPrimary();
        }

        return true;
    }

    public function afterDelete($event = null)
    {
        if (!$this->handlePrimary()) { return true; }
        $this->handOffPrimary();
    }

    public function handOffPrimary()
    {
        if ($this->owner->isPrimary || $this->owner->wasPrimary) {
            // assign a new primary
            $siblings = $this->getSiblings(false);
            if (!empty($siblings)) {
                $sibling = array_shift($siblings);
                $sibling->setPrimary();
            }
        }

        return true;
    }

    public function setPrimary()
    {
        if (!$this->handlePrimary()) { return false; }
        $primarySiblings = $this->getSiblings(true);
        foreach ($primarySiblings as $sibling) {
            $sibling->{$this->primaryField} = 0;
            if (!$sibling->save()) {
                return false;
            }
        }
        $this->owner->{$this->primaryField} = 1;

        return $this->owner->save();
    }

    public function getIsPrimary()
    {
        if (!$this->handlePrimary()) { return false; }

        return !empty($this->owner->{$this->primaryField});
    }

    public function getPresentSetPrimaryOption()
    {
        if (!$this->handlePrimary()) { return false; }

        return empty($this->owner->{$this->primaryField});
    }
}
