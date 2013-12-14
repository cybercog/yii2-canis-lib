<?php
/**
 * library/web/User.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\web;

use Yii;

class User extends \yii\web\User
{
	public function loginRequired()
	{
		$request = Yii::$app->getRequest();
		$this->setReturnUrl($request->getUrl());
		return parent::loginRequired();
	}
}
