<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use Yii;

/**
 * User [@doctodo write class description for User]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class User extends \yii\web\User
{
    /**
    * @inheritdoc
    **/
    public function loginRequired()
    {
        $request = Yii::$app->getRequest();
        $this->setReturnUrl($request->getUrl());

        return parent::loginRequired();
    }
}
