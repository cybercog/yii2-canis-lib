<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UpdateEvent [[@doctodo class_description:infinite\db\behaviors\auditable\UpdateEvent]].
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
        return new \infinite\base\language\Verb('update');
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'in';
    }
}
