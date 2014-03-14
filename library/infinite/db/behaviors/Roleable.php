<?php
namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;

class Roleable extends \infinite\db\behaviors\ActiveRecord
{

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }
    
    public function safeAttributes()
    {
        return [];
    }

    public function isEnabled()
    {
        if (empty($this->owner->getBehavior('Registry')) || !$this->ownableEnabled) {
            return false;
        }
        return true;
    }

    public function afterSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if (empty($this->owner->getBehavior('ActiveAccess'))) { return; }
        if (!empty($this->owner->getBehavior('Relatable'))) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->owner->hasOwner()) { return; }
        $owner = $this->owner->getObjectOwner(true);
        foreach ($this->ownerAccess() as $aca) {
            if (!$this->owner->can($aca, $owner)) {
                $this->owner->allow($aca, $owner);
            }
        }
    }
}
