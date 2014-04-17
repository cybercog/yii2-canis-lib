<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

/**
 * Match [@doctodo write class description for Match]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Match extends \infinite\base\Component
{
    public $regex;
    public $value;
    public $not = false;

    /**
     *
     *
     * @param unknown $value
     * @param unknown $not   (optional)
     * @param unknown $type  (optional)
     */
    public function __construct($value, $not = false, $type = 'regex')
    {
        if (substr($value, 0, 1) === '/' and $type === 'regex') {
            $this->regex = $value;
        } else {
            $this->value = $value;
        }
        $this->not = $not;
    }

    /**
     *
     *
     * @param  unknown $test
     * @return unknown
     */
    public function test($test)
    {
        if (isset($this->value)) {
            if (is_array($this->value)) {
                if ($this->not) {
                    return !in_array($test, $this->value);
                } else {
                    return in_array($test, $this->value);
                }
            } else {
                if ($this->not) {
                    return $this->value !== $test;
                } else {
                    return $this->value === $test;
                }
            }
        } elseif (isset($this->regex)) {
            if ($this->not) {
                return preg_match($this->regex, $test) !== 1;
            } else {
                return preg_match($this->regex, $test) === 1;
            }
        }

        return false;
    }
}
