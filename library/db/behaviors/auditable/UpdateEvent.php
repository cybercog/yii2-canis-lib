<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * UpdateEvent [[@doctodo class_description:teal\db\behaviors\auditable\UpdateEvent]].
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
        return new \teal\base\language\Verb('update');
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'in';
    }
}
