<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteRelationEvent [@doctodo write class description for DeleteRelationEvent].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class CreateRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'create_relation';

    public function getVerb()
    {
        return new \infinite\base\language\Verb('link');
    }
}
