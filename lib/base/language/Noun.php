<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base\language;

/**
 * Noun [[@doctodo class_description:canis\base\language\Noun]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Noun extends \canis\base\language\Word
{
    /**
     * @var [[@doctodo var_type:_singular]] [[@doctodo var_description:_singular]]
     */
    protected $_singular;
    /**
     * @var [[@doctodo var_type:_plural]] [[@doctodo var_description:_plural]]
     */
    protected $_plural;
    /**
     * @var [[@doctodo var_type:_possessive]] [[@doctodo var_description:_possessive]]
     */
    protected $_possessive;
    /**
     * @var [[@doctodo var_type:_possessive_plural]] [[@doctodo var_description:_possessive_plural]]
     */
    protected $_possessive_plural;

    /**
     * Constructor.
     *
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
     *
     * @return unknown
     */
    public function __toString()
    {
        return $this->_singular;
    }

    /**
     * @inheritdoc
     */
    public function getBase()
    {
        return $this->_singular;
    }

    /**
     * Get upper singular.
     *
     * @return [[@doctodo return_type:getUpperSingular]] [[@doctodo return_description:getUpperSingular]]
     */
    public function getUpperSingular()
    {
        return $this->getSingular(true);
    }

    /**
     * Get singular.
     *
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function getSingular($upper = false)
    {
        return $this->prepare($this->_singular, $upper);
    }

    /**
     * Get upper plural.
     *
     * @return [[@doctodo return_type:getUpperPlural]] [[@doctodo return_description:getUpperPlural]]
     */
    public function getUpperPlural()
    {
        return $this->getPlural(true);
    }

    /**
     * Get plural.
     *
     * @param unknown $upper (optional)
     *
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
     * Get possessive.
     *
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function getPossessive($upper = false)
    {
        if (is_null($this->_possessive)) {
            if (substr($this->_singular, -1) === 's') {
                $this->_possessive = $this->_singular . '\'';
            } else {
                $this->_possessive = $this->_singular . '\'s';
            }
        }

        return $this->prepare($this->_possessive, $upper);
    }

    /**
     * Get possessive plural.
     *
     * @param unknown $upper (optional)
     *
     * @return unknown
     */
    public function getPossessivePlural($upper = false)
    {
        if (is_null($this->_possessive_plural)) {
            if (substr($this->plural, -1) === 's') {
                $this->_possessive_plural = $this->plural . '\'';
            } else { // wahuh?
                $this->_possessive_plural = $this->_singular . '\'s';
            }
        }

        return $this->prepare($this->_possessive_plural, $upper);
    }

    /**
     * Get package.
     *
     * @param boolean $upper [[@doctodo param_description:upper]] [optional]
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
    public function getPackage($upper = true)
    {
        return [
            'singular' => $this->getSingular($upper),
            'plural' => $this->getPlural($upper),
            'possessivePlural' => $this->getPossessivePlural($upper),
            'possessive' => $this->getPossessive($upper),
        ];
    }
}
