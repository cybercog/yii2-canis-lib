<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * UpdateEvent [[@doctodo class_description:canis\db\behaviors\auditable\UpdateEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UpdateEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'update';
    /**
     * @inheritdoc
     */
    public $attributes;

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
    public function getIndirectConnector()
    {
        return 'in';
    }
}
