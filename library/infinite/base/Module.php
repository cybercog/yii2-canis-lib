<?php
namespace infinite\base;

class Module extends \yii\base\Module {
	use \infinite\base\ObjectTrait;

	protected $_shortName;

	/**
	 *
	 *
	 * @param unknown $value
	 */
	public function getModuleName() {
		$this->_shortName = $value;
	}

	/**
	 *
	 *
	 * @param unknown $value
	 */
	public function setShortName($value) {
		$this->_shortName = $value;
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function getShortName() {
		if (!is_null($this->_shortName)) {
			return $this->_shortName;
		}
		return $this->_shortName = preg_replace('/('.ucfirst($this->id).')([A-Za-z]+)(Module)/', '\\2', self::baseClassName());
	}
}
?>