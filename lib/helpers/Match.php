<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\helpers;

/**
 * Match [[@doctodo class_description:teal\helpers\Match]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Match extends \teal\base\Component
{
    /**
     * @var [[@doctodo var_type:regex]] [[@doctodo var_description:regex]]
     */
    public $regex;
    /**
     * @var [[@doctodo var_type:value]] [[@doctodo var_description:value]]
     */
    public $value;
    /**
     * @var [[@doctodo var_type:not]] [[@doctodo var_description:not]]
     */
    public $not = false;

    /**
     * Constructor.
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
     * [[@doctodo method_description:test]].
     *
     * @param unknown $test
     *
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
