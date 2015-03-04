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
 * Authority [@doctodo write class description for Authority].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Authority extends \infinite\base\Component
{
    /**
     */
    protected $_handler;

    /**
     * Set handler.
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
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Get requestors.
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
     */
    public function getTopRequestors($accessingObject)
    {
        if (is_null($this->handler)) {
            return false;
        }

        return $this->handler->getTopRequestors($accessingObject);
    }
}
