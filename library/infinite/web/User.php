<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
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
