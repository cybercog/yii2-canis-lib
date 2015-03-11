<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security;

use teal\base\exceptions\Exception;

/**
 * Authority [[@doctodo class_description:teal\security\Authority]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Authority extends \teal\base\Component
{
    /**
     * @var [[@doctodo var_type:_handler]] [[@doctodo var_description:_handler]]
     */
    protected $_handler;

    /**
     * Set handler.
     *
     * @param [[@doctodo param_type:handler]] $handler [[@doctodo param_description:handler]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     */
    public function setHandler($handler)
    {
        if ($handler->getBehavior('Authority') === null) {
            throw new Exception("Handler passed to the authority engine is not valid.");
        }
        $this->_handler = $handler;
    }

    /**
     * Get handler.
     *
     * @return [[@doctodo return_type:getHandler]] [[@doctodo return_description:getHandler]]
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Get requestors.
     *
     * @param [[@doctodo param_type:accessingObject]] $accessingObject [[@doctodo param_description:accessingObject]]
     *
     * @return [[@doctodo return_type:getRequestors]] [[@doctodo return_description:getRequestors]]
     */
    public function getRequestors($accessingObject)
    {
        if (is_null($this->handler)) {
            return false;
        }

        return $this->handler->getRequestors($accessingObject);
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
        if (is_null($this->handler)) {
            return false;
        }

        return $this->handler->getTopRequestors($accessingObject);
    }
}
