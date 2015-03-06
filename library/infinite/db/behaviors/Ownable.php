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
 * Ownable [[@doctodo class_description:infinite\db\behaviors\Ownable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Ownable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:_table]] [[@doctodo var_description:_table]]
     */
    public static $_table;
    /**
     * @var [[@doctodo var_type:ownableEnabled]] [[@doctodo var_description:ownableEnabled]]
     */
    public $ownableEnabled = true;
    const ROLE_OWNER = 'owner';

    /**
     * @inheritdoc
     */
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
     */
    public function safeAttributes()
    {
        return ['ownableEnabled', 'objectOwner'];
    }

    /**
     * [[@doctodo method_description:isEnabled]].
     *
     * @return [[@doctodo return_type:isEnabled]] [[@doctodo return_description:isEnabled]]
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
     * [[@doctodo method_description:determineOwner]].
     *
     * @return [[@doctodo return_type:determineOwner]] [[@doctodo return_description:determineOwner]]
     */
    public function determineOwner()
    {
        if (isset(Yii::$app->user) && !Yii::$app->user->isGuest && isset(Yii::$app->user->id)) {
            return Yii::$app->user->id;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:ownerAccess]].
     *
     * @return [[@doctodo return_type:ownerAccess]] [[@doctodo return_description:ownerAccess]]
     */
    public function ownerAccess()
    {
        return false;
    }

    /**
     * [[@doctodo method_description:beforeSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeSave]] [[@doctodo return_description:beforeSave]]
     */
    public function beforeSave($event)
    {
        if (!$this->isEnabled()) {
            return;
        }
        if ($this->owner->hasObjectOwner()) {
            return;
        }
        if (($owner = $this->determineOwner()) && $owner) {
            $this->owner->objectOwner = $owner;
        }
    }

    /**
     * [[@doctodo method_description:hasObjectOwner]].
     *
     * @return [[@doctodo return_type:hasObjectOwner]] [[@doctodo return_description:hasObjectOwner]]
     */
    public function hasObjectOwner()
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $owner = $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);

        return !empty($owner);
    }

    /**
     * Set object owner.
     *
     * @param [[@doctodo param_type:aro]] $aro [[@doctodo param_description:aro]]
     *
     * @return [[@doctodo return_type:setObjectOwner]] [[@doctodo return_description:setObjectOwner]]
     */
    public function setObjectOwner($aro)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->owner->setRole(['system_id' => self::ROLE_OWNER], $aro);
    }

    /**
     * Get object owner.
     *
     * @return [[@doctodo return_type:getObjectOwner]] [[@doctodo return_description:getObjectOwner]]
     */
    public function getObjectOwner()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
    }

    /**
     * [[@doctodo method_description:afterSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:afterSave]] [[@doctodo return_description:afterSave]]
     */
    public function afterSave($event)
    {
        if (!$this->isEnabled()) {
            return;
        }
        if ($this->owner->getBehavior('ActiveAccess') === null) {
            return;
        }
        $ownerAccess = $this->owner->ownerAccess();
        if ($ownerAccess === false) {
            return;
        }
        if ($this->owner->getBehavior('Relatable') !== null) {
            $this->owner->handleRelationSave($event);
        }
        if (!$this->owner->hasObjectOwner()) {
            return;
        }

        $owner = $this->owner->getFirstAroByRole(['system_id' => self::ROLE_OWNER]);
        foreach ($ownerAccess as $aca) {
            if (!$this->owner->can($aca, $owner)) {
                $this->owner->allow($aca, $owner);
            }
        }
    }
}
