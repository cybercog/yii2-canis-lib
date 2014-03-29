<?php
/**
 * library/db/behaviors/Registry.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;

class Ownable extends \infinite\db\behaviors\ActiveRecord
{
    public static $_table;
    public $ownableEnabled = true;
    const ROLE_OWNER = 'owner';

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',

            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }
    
    public function safeAttributes()
    {
        return ['ownableEnabled', 'objectOwner'];
    }

    public function isEnabled()
    {
        if ($this->owner->getBehavior('Registry') === null
            || $this->owner->getBehavior('Roleable') === null 
            || !$this->owner->getBehavior('Roleable')->isEnabled()
            || !$this->owner->ownableEnabled
            ) {
            return false;
        }
        return true;
    }

    public function determineOwner()
    {
        if (isset(Yii::$app->user) && !Yii::$app->user->isGuest && isset(Yii::$app->user->id)) {
            return Yii::$app->user->id;
        }
        return false;
    }

    public function ownerAccess()
    {
        return false;
    }

    public function beforeSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if ($this->owner->hasObjectOwner()) { return; }
        if (($owner = $this->determineOwner()) && $owner) {
            $this->owner->objectOwner = $owner;
        }
    }

    public function hasObjectOwner()
    {
        if (!$this->isEnabled()) { return false; }
        $owner = $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
        return !empty($owner);
    }

    public function setObjectOwner($aro)
    {
        if (!$this->isEnabled()) { return false; }
        return $this->owner->setRole(['system_id' => self::ROLE_OWNER], $aro);
    }

    public function getObjectOwner()
    { 
        if (!$this->isEnabled()) { return false; }
        return $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
    }

    public function afterSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if ($this->owner->getBehavior('ActiveAccess') === null) { return; }
        $ownerAccess = $this->owner->ownerAccess();
        if ($ownerAccess === false) { return; }
        if ($this->owner->getBehavior('Relatable') !== null) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->owner->hasObjectOwner()) { return; }

        $owner = $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
        foreach ($ownerAccess as $aca) {
            if (!$this->owner->can($aca, $owner)) {
                $this->owner->allow($aca, $owner);
            }
        }
    }
}
