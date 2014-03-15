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
        if (empty($this->owner->getBehavior('Registry')) 
            || empty($this->owner->getBehavior('Roleable')) 
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
        \d($owner);exit;
        }
    }

    public function hasObjectOwner()
    {
        if (!$this->isEnabled()) { return false; }
        return !empty($this->owner->getFirstAroByRole(self::ROLE_OWNER));
    }

    public function setObjectOwner($aro)
    {
        if (!$this->isEnabled()) { return false; }
        return $this->owner->setRole(self::ROLE_OWNER, $aro);
    }

    public function getObjectOwner()
    { 
        return $this->owner->getFirstAroByRole(self::ROLE_OWNER);
    }

    public function afterSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if (empty($this->owner->getBehavior('ActiveAccess'))) { return; }
        $ownerAccess = $this->owner->ownerAccess();
        if ($ownerAccess === false) { return; }
        if (!empty($this->owner->getBehavior('Relatable'))) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->owner->hasRole(self::ROLE_OWNER)) { return; }

        $owner = $this->owner->getFirstAroByRole(self::ROLE_OWNER);
        foreach ($ownerAccess as $aca) {
            if (!$this->owner->can($aca, $owner)) {
                $this->owner->allow($aca, $owner);
            }
        }
    }
}
