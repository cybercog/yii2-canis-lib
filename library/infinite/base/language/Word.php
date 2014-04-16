<?php
/**
 * library/base/language/Word.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

namespace infinite\base\language;

class Word extends \infinite\base\Object
{

    /**
     *
     *
     * @param  unknown $s
     * @param  unknown $upper (optional)
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
     *
     *
     * @param  unknown $l
     * @param  unknown $includeY (optional)
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
     *
     *
     * @param  unknown $s
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
