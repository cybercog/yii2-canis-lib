<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use infinite\base\exceptions\Exception;

/**
 * Authority [[@doctodo class_description:infinite\security\Authority]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Authority extends \infinite\base\Component
{
    /**
     * @var [[@doctodo var_type:_handler]] [[@doctodo var_description:_handler]]
     */
    protected $_handler;

    /**
     * Set handler.
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
