<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\language;

/**
 * Verb [@doctodo write class description for Verb]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Verb extends \infinite\base\language\Word
{
    /**
     * @var __var__base_type__ __var__base_description__
     */
    protected $_base;
    /**
     * @var __var__active_type__ __var__active_description__
     */
    protected $_active;
    /**
     * @var __var__past_type__ __var__past_description__
     */
    protected $_past;

    /**
     * Constructor.
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
     * @return unknown
     */
    public function __toString()
    {
        return $this->_base;
    }

    public function getBase()
    {
        return $this->_base;
    }

    /**
     * Get active
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getActive($upper = false)
    {
        if (is_null($this->_active)) {
            if (substr($this->_base, -1) === 'e') {
                $this->_active = substr($this->_base, 0, -1) .'ing';
            } elseif ($this->isCVC($this->_base)) {
                $this->_active = $this->_base . substr($this->_base, -1) . 'ing';
            } else {
                $this->_active = $this->_base .'ing';
            }
        }

        return $this->prepare($this->_active, $upper);
    }

    /**
     * Get past
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getPast($upper = false)
    {
        if (is_null($this->_past)) {
            if (substr($this->_base, -1) === 'y') {
                $this->_past = substr($this->_base, 0, -1) .'ied';
            } elseif ($this->isCVC($this->_base) and !in_array(substr($this->_base, -1), ['w', 'x', 'z'])) {
                $this->_past = $this->_base . substr($this->_base, -1) . 'ed';
            } elseif (in_array(substr($this->_base, -1), ['e'])) {
                $this->_past = $this->_base . 'd';
            } else {
                $this->_past = $this->_base .'ed';
            }
        }

        return $this->prepare($this->_past, $upper);
    }
}
