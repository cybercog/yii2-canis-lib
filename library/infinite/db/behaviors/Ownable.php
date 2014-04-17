<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

/**
 * Ownable [@doctodo write class description for Ownable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Ownable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var__table_type__ __var__table_description__
     */
    public static $_table;
    /**
     * @var __var_ownableEnabled_type__ __var_ownableEnabled_description__
     */
    public $ownableEnabled = true;
    const ROLE_OWNER = 'owner';

    /**
    * @inheritdoc
    **/
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',

            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
    * @inheritdoc
    **/
    public function safeAttributes()
    {
        return ['ownableEnabled', 'objectOwner'];
    }

    /**
     * __method_isEnabled_description__
     * @return __return_isEnabled_type__ __return_isEnabled_description__
     */
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

    /**
     * __method_determineOwner_description__
     * @return __return_determineOwner_type__ __return_determineOwner_description__
     */
    public function determineOwner()
    {
        if (isset(Yii::$app->user) && !Yii::$app->user->isGuest && isset(Yii::$app->user->id)) {
            return Yii::$app->user->id;
        }

        return false;
    }

    /**
     * __method_ownerAccess_description__
     * @return __return_ownerAccess_type__ __return_ownerAccess_description__
     */
    public function ownerAccess()
    {
        return false;
    }

    /**
     * __method_beforeSave_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_beforeSave_type__ __return_beforeSave_description__
     */
    public function beforeSave($event)
    {
        if (!$this->isEnabled()) { return; }
        if ($this->owner->hasObjectOwner()) { return; }
        if (($owner = $this->determineOwner()) && $owner) {
            $this->owner->objectOwner = $owner;
        }
    }

    /**
     * __method_hasObjectOwner_description__
     * @return __return_hasObjectOwner_type__ __return_hasObjectOwner_description__
     */
    public function hasObjectOwner()
    {
        if (!$this->isEnabled()) { return false; }
        $owner = $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);

        return !empty($owner);
    }

    /**
     * __method_setObjectOwner_description__
     * @param __param_aro_type__ $aro __param_aro_description__
     * @return __return_setObjectOwner_type__ __return_setObjectOwner_description__
     */
    public function setObjectOwner($aro)
    {
        if (!$this->isEnabled()) { return false; }

        return $this->owner->setRole(['system_id' => self::ROLE_OWNER], $aro);
    }

    /**
     * __method_getObjectOwner_description__
     * @return __return_getObjectOwner_type__ __return_getObjectOwner_description__
     */
    public function getObjectOwner()
    {
        if (!$this->isEnabled()) { return false; }

        return $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
    }

    /**
     * __method_afterSave_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterSave_type__ __return_afterSave_description__
     */
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
