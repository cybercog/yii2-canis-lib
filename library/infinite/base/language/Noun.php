<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\language;

/**
 * Noun [@doctodo write class description for Noun]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Noun extends \infinite\base\language\Word
{
    /**
     * @var __var__singular_type__ __var__singular_description__
     */
    protected $_singular;
    /**
     * @var __var__plural_type__ __var__plural_description__
     */
    protected $_plural;
    /**
     * @var __var__possessive_type__ __var__possessive_description__
     */
    protected $_possessive;
    /**
     * @var __var__possessive_plural_type__ __var__possessive_plural_description__
     */
    protected $_possessive_plural;

    /**
     * Constructor.
     * @param unknown $singular
     * @param unknown $variants (optional)
     */
    public function __construct($singular, $variants = [])
    {
        $this->_singular = strtolower($singular);
        foreach ($variants as $k => $v) {
            switch ($k) {
            case 'plural':
                $this->_plural = $v;
                break;
            case 'possessive':
                $this->_possessive = $v;
                break;
            case 'possessive_plural':
                $this->_possessive_plural = $v;
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
        return $this->_singular;
    }

    /**
     * Get upper singular
     * @return __return_getUpperSingular_type__ __return_getUpperSingular_description__
     */
    public function getUpperSingular()
    {
        return $this->getSingular(true);
    }

    /**
     * Get singular
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getSingular($upper = false)
    {
        return $this->prepare($this->_singular, $upper);
    }

    /**
     * Get upper plural
     * @return __return_getUpperPlural_type__ __return_getUpperPlural_description__
     */
    public function getUpperPlural()
    {
        return $this->getPlural(true);
    }

    /**
     * Get plural
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getPlural($upper = false)
    {
        if (is_null($this->_plural)) {
            $this->_plural = Inflector::pluralize($this->_singular);
        }

        return $this->prepare($this->_plural, $upper);
    }

    /**
     * Get possessive
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getPossessive($upper = false)
    {
        if (is_null($this->_possessive)) {
            if (substr($this->_singular, -1) === 's') {
                $this->_possessive = $this->_singular .'\'';
            } else {
                $this->_possessive = $this->_singular .'\'s';
            }
        }

        return $this->prepare($this->_possessive, $upper);
    }

    /**
     * Get possessive plural
     * @param unknown $upper (optional)
     * @return unknown
     */
    public function getPossessivePlural($upper = false)
    {
        if (is_null($this->_possessive_plural)) {
            if (substr($this->plural, -1) === 's') {
                $this->_possessive_plural = $this->plural .'\'';
            } else { // wahuh?
                $this->_possessive_plural = $this->_singular .'\'s';
            }
        }

        return $this->prepare($this->_possessive_plural, $upper);
    }

    public function getPackage($upper = true)
    {
        return [
            'singular' => $this->getSingular($upper),
            'plural' => $this->getPlural($upper),
            'possessivePlural' => $this->getPossessivePlural($upper),
            'possessive' => $this->getPossessive($upper)
        ];
    }
}
