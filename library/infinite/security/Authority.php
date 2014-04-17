<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security;

use infinite\base\exceptions\Exception;

/**
 * Authority [@doctodo write class description for Authority]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Authority extends \infinite\base\Component
{
    /**
     * @var __var__handler_type__ __var__handler_description__
     */
    protected $_handler;

    /**
     * Set handler
     * @param __param_handler_type__ $handler __param_handler_description__
     * @throws Exception __exception_Exception_description__
     */
    public function setHandler($handler)
    {
        if ($handler->getBehavior('Authority') === null) {
            throw new Exception("Handler passed to the authority engine is not valid.");
        }
        $this->_handler = $handler;
    }

    /**
     * Get handler
     * @return __return_getHandler_type__ __return_getHandler_description__
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Get requestors
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__
     * @return __return_getRequestors_type__ __return_getRequestors_description__
     */
    public function getRequestors($accessingObject)
    {
        if (is_null($this->handler)) {
            return false;
        }

        return $this->handler->getRequestors($accessingObject);
    }

    /**
     * Get top requestors
     * @param __param_accessingObject_type__ $accessingObject __param_accessingObject_description__
     * @return __return_getTopRequestors_type__ __return_getTopRequestors_description__
     */
    public function getTopRequestors($accessingObject)
    {
        if (is_null($this->handler)) {
            return false;
        }

        return $this->handler->getTopRequestors($accessingObject);
    }
}
