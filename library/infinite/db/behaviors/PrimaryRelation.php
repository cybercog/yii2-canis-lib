<?php
namespace infinite\db\behaviors;

use infinite\db\models\Relation;

class PrimaryRelation extends \infinite\db\behaviors\ActiveRecord
{
	public $primaryField = 'primary';

	public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
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
			$relationFields[$this->primaryField] = 1;
		}
		return $this->owner->siblingRelations($parentObject, ['fields' => $relationFields], ['disableAccess' => true]);
	}

	public function beforeInsert($event = null)
	{
		if (!$this->handlePrimary()) { return true; }
		$primarySiblings = $this->getSiblings(true);
		if (empty($primarySiblings)) {
			$this->owner->{$this->primaryField} = 1;
		}
		return true;
	}


	public function afterDelete($event = null)
	{
		if (!$this->handlePrimary()) { return true; }
		if ($this->isPrimary()) {
			// assign a new primary
			$siblings = $this->getSiblings(false);
			if (!empty($siblings)) {
				$sibling = array_shift($siblings);

			}
		}
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