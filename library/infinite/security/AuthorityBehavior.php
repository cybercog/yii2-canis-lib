<?php
namespace infinite\security;

class AuthorityBehavior extends \yii\base\Behavior {
	/**
	 * @inheritdoc
	 */
	public function getRequestors($accessingObject, $firstLevel = true)
	{
		return false;
	}


	public function getTopRequestors($accessingObject)
	{
		return false;
	}
}