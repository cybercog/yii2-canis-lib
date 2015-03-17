<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base\language;

/**
 * Word [[@doctodo class_description:canis\base\language\Word]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Word extends \canis\base\Object
{
    /**
     * Get base.
     */
    abstract public function getBase();
    /**
     * [[@doctodo method_description:prepare]].
     *
     * @param unknown $s
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function prepare($s, $upper = false)
    {
        if (!$upper) {
            return $s;
        }

        return ucwords($s);
    }

    /**
     * [[@doctodo method_description:vowel]].
     *
     * @param unknown $l
     * @param unknown $includeY (optional)
     *
     * @return unknown
     */
    public function vowel($l, $includeY = false)
    {
        if ($includeY) {
            return in_array($l, ['a', 'e', 'i', 'o', 'u', 'y']);
        }

        return in_array($l, ['a', 'e', 'i', 'o', 'u']);
    }

    /**
     * [[@doctodo method_description:isCVC]].
     *
     * @param unknown $s
     *
     * @return unknown
     */
    public function isCVC($s)
    {
        if (strlen($s) > 3) {
            $s = substr($s, -3);
        }

        return strlen($s) === 3 and (!$this->vowel(substr($s, 0, 1)) and $this->vowel(substr($s, 1, 1)) and !$this->vowel(substr($s, 2, 1)));
    }
}
