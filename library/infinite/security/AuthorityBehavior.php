<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

/**
 * AuthorityBehavior [@doctodo write class description for AuthorityBehavior].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AuthorityBehavior extends \yii\base\Behavior
{
    /**
     * @inheritdoc
     */
    public function getRequestors($accessingObject, $firstLevel = true)
    {
        return false;
    }

    /**
     * Get top requestors.
     */
    public function getTopRequestors($accessingObject)
    {
        return false;
    }
}
