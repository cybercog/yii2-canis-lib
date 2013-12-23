<?php
/**
 * library/base/language/Noun.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base\language;



class Noun extends \infinite\base\language\Word
{
	protected $_singular;
	protected $_plural;
	protected $_possessive;
	protected $_possessive_plural;

	/**
	 *
	 *
	 * @param unknown $singular
	 * @param unknown $variants (optional)
	 */
	public function __construct($singular, $variants = []) {
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
	 *
	 *
	 * @return unknown
	 */
	public function __toString() {
		return $this->_singular;
	}

	public function getUpperSingular() {
		return $this->getSingular(true);
	}

	/**
	 *
	 *
	 * @param unknown $upper (optional)
	 * @return unknown
	 */
	public function getSingular($upper = false) {
		return $this->prepare($this->_singular, $upper);
	}


	public function getUpperPlural() {
		return $this->getPlural(true);
	}

	/**
	 *
	 *
	 * @param unknown $upper (optional)
	 * @return unknown
	 */
	public function getPlural($upper = false) {
		if (is_null($this->_plural)) {
			$this->_plural = Inflector::pluralize($this->_singular);
		}
		return $this->prepare($this->_plural, $upper);
	}


	/**
	 *
	 *
	 * @param unknown $upper (optional)
	 * @return unknown
	 */
	public function getPossessive($upper = false) {
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
	 *
	 *
	 * @param unknown $upper (optional)
	 * @return unknown
	 */
	public function getPossessivePlural($upper = false) {
		if (is_null($this->_possessive_plural)) {
			if (substr($this->plural, -1) === 's') {
				$this->_possessive_plural = $this->plural .'\'';
			} else { // wahuh?
				$this->_possessive_plural = $this->_singular .'\'s';
			}
		}
		return $this->prepare($this->_possessive_plural, $upper);
	}
}
