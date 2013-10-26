<?php
/**
 * library/base/Component.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base;

class Component extends \yii\base\Component
{
	use \infinite\base\ObjectTrait;
	
	public function hasBehavior($name)
	{
		return isset($this->_behaviors[$name]);
	}

}
