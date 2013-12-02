<?php
namespace infinite\web;

use Yii;

class State extends \infinite\base\Object
{
	const SESSION_STATE_KEY = '_s';

	public function get($key, $default = null)
	{
		if (isset(Yii::$app->session)) {
			$state = Yii::$app->session[self::SESSION_STATE_KEY];
			if (isset($state[$key])) {
				return $state[$key];
			}
		}
		return $default;
	}

	public function set($key, $value)
	{
		if (isset(Yii::$app->session)) {
			$state = Yii::$app->session[self::SESSION_STATE_KEY];
			if (empty($state)) {
				$state = [];
			}
			$state[$key] = $value;
			Yii::$app->session[self::SESSION_STATE_KEY] = $state;
			return true;
		}
		return false;
	}
}