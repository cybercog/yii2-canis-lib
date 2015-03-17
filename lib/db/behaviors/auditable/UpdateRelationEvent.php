<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * UpdateRelationEvent [[@doctodo class_description:canis\db\behaviors\auditable\UpdateRelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UpdateRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    public $saveOnRegister = true;
    /**
     * @inheritdoc
     */
    protected $_id = 'update_relation';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \canis\base\language\Verb('update');
    }

    /**
     * @inheritdoc
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' link with {{directObject}}' . $this->indirectStory;
    }
}
