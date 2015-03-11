<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security;

/**
 * AuthorityBehavior [[@doctodo class_description:teal\security\AuthorityBehavior]].
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
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]]
     *
     * @return [[@doctodo return_type:getTopRequestors]] [[@doctodo return_description:getTopRequestors]]
     */
    public function getTopRequestors($accessingObject)
    {
        return false;
    }
}
