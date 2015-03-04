<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\language;

/**
 * Word [@doctodo write class description for Word].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Word extends \infinite\base\Object
{
    abstract public function getBase();
    /**
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
