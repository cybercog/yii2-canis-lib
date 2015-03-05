<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

/**
 * Collector [[@doctodo class_description:infinite\base\collector\Collector]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Collector extends \infinite\base\Component
{
    use CollectorTrait;

    /**
     * @var [[@doctodo var_type:_systemId]] [[@doctodo var_description:_systemId]]
     */
    protected $_systemId;

    const DEFAULT_BUCKET = '__default';
    const EVENT_AFTER_COLLECTOR_INIT = 'afterCollectorInit';
    const EVENT_BEFORE_BUCKET_ACCESS = 'beforeBucketAccess';

    /**
     * Set system.
     *
     * @param unknown $value
     */
    public function setSystemId($value)
    {
        $this->_systemId = $value;
    }

    /**
     * Get system.
     *
     * @return unknown
     */
    public function getSystemId()
    {
        return $this->_systemId;
    }
}
