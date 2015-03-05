<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * CreateRelationEvent [[@doctodo class_description:infinite\db\behaviors\auditable\CreateRelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class CreateRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'create_relation';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \infinite\base\language\Verb('link');
    }
}
