<?php
namespace infinite\security;

use infinite\base\exceptions\Exception;
use yii\db\Query;

class Authority extends \infinite\base\Component
{
	protected $_handler;

	public function setHandler($handler)
	{
		if (!($handler instanceof AuthorityInterface)) {
			throw new Exception("Handler passed to the authority engine is not valid.");
		}
		$this->_handler = $handler;
	}

	public function getHandler()
	{
		return $this->_handler;
	}

	public function getRequestors($accessingObject)
	{
		if (is_null($this->handler)) {
			return false;
		}
		return $this->handler->getRequestors($accessingObject);
	}
}
?>