<?php
namespace infinite\base;

abstract class Module extends \yii\base\Module {
	use \infinite\base\ObjectTrait;

	protected $_shortName;

	abstract public function getModuleType();
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
		preg_match('/'.ucfirst($this->moduleType).'([A-Za-z]+)\\\Module/', get_class($this), $matches);
		if (!isset($matches[1])) {
			throw new Exception(get_class($this). " is not set up correctly!");
		}
		return $this->_shortName = $matches[1];
	}
}
?>