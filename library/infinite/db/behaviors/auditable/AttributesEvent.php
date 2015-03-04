<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * AttributesEvent [@doctodo write class description for AttributesEvent].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AttributesEvent extends Event
{
    /**
     */
    public $attributes;

    /**
     * @inheritdoc
     */
    public function getHashArray()
    {
        $hash = parent::getHashArray();
        $attributes = $this->attributes;
        ksort($attributes);
        $hash['attributes'] = $attributes;

        return $hash;
    }
}
