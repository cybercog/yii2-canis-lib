<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

use Yii;

/**
 * User [[@doctodo class_description:teal\web\User]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class User extends \yii\web\User
{
    /**
     * @var [[@doctodo var_type:isAnonymous]] [[@doctodo var_description:isAnonymous]]
     */
    public $isAnonymous = false;

    /**
     * @inheritdoc
     */
    public function getIdentity($autoRenew = true)
    {
        if ($this->isAnonymous) {
            return;
        }

        return parent::getIdentity($autoRenew);
    }

    /**
     * @inheritdoc
     */
    public function loginRequired($checkAjax = true)
    {
        $checkAjax = false;
        $request = Yii::$app->getRequest();
        $this->setReturnUrl($request->getUrl());

        return parent::loginRequired($checkAjax);
    }
}
