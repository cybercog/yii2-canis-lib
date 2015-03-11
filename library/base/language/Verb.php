<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base\language;

/**
 * Verb [[@doctodo class_description:teal\base\language\Verb]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Verb extends \teal\base\language\Word
{
    /**
     * @var [[@doctodo var_type:_base]] [[@doctodo var_description:_base]]
     */
    protected $_base;
    /**
     * @var [[@doctodo var_type:_active]] [[@doctodo var_description:_active]]
     */
    protected $_active;
    /**
     * @var [[@doctodo var_type:_past]] [[@doctodo var_description:_past]]
     */
    protected $_past;

    /**
     * Constructor.
     *
     * @param unknown $base
     * @param unknown $variants (optional)
     */
    public function __construct($base, $variants = [])
    {
        $this->_base = strtolower($base);
        foreach ($variants as $k => $v) {
            switch ($k) {
            case 'active':
                $this->_active = $v;
                break;
            case 'past':
                $this->_past = $v;
                break;
            }
        }
    }

    /**
     * Converts object to string.
     *
     * @return unknown
     */
    public function __toString()
    {
        return $this->_base;
    }

    /**
     * @inheritdoc
     */
    public function getBase()
    {
        return $this->_base;
    }

    /**
     * Get simple present.
     *
     * @param boolean $upper [[@doctodo param_description:upper]] [optional]
     *
     * @return [[@doctodo return_type:getSimplePresent]] [[@doctodo return_description:getSimplePresent]]
     */
    public function getSimplePresent($upper = false)
    {
        return $this->prepare($this->base, $upper);
    }

    /**
     * Get active.
     *
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function getActive($upper = false)
    {
        if (is_null($this->_active)) {
            if (substr($this->_base, -1) === 'e') {
                $this->_active = substr($this->_base, 0, -1) . 'ing';
            } elseif ($this->isCVC($this->_base)) {
                $this->_active = $this->_base . substr($this->_base, -1) . 'ing';
            } else {
                $this->_active = $this->_base . 'ing';
            }
        }

        return $this->prepare($this->_active, $upper);
    }

    /**
     * Get past.
     *
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function getPast($upper = false)
    {
        if (is_null($this->_past)) {
            if (substr($this->_base, -1) === 'y') {
                $this->_past = substr($this->_base, 0, -1) . 'ied';
            } elseif ($this->isCVC($this->_base) and !in_array(substr($this->_base, -1), ['w', 'x', 'z'])) {
                $this->_past = $this->_base . substr($this->_base, -1) . 'ed';
            } elseif (in_array(substr($this->_base, -1), ['e'])) {
                $this->_past = $this->_base . 'd';
            } else {
                $this->_past = $this->_base . 'ed';
            }
        }

        return $this->prepare($this->_past, $upper);
    }
}
