<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

use Yii;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Creator extends \infinite\base\Component implements CreatorInterface
{
    protected $_identityProvider;
    public $priority = 0;
    public $usernameFilter = false;

    public function getIdentityProvider()
    {
        return $this->_identityProvider;
    }

    public function setIdentityProvider($idp)
    {
        $this->_identityProvider = $idp;
    }

    public function attemptCreate($username, $password)
    {
        $username = $this->getUsername($username);

        return $this->internalAttemptCreate($username, $password);
    }

    abstract protected function internalAttemptCreate($username, $password);

    public function getUsername($username)
    {
        if ($this->usernameFilter) {
            return call_user_func($this->usernameFilter, $username);
        }

        return $username;
    }

    protected function createUser($attributes)
    {
        if (empty($attributes['email']) || empty($attributes['first_name']) || empty($attributes['last_name'])) {
            return false;
        }
        if (empty($this->identityProvider->object->primaryKey)) {
            return false;
        }
        if ($this->userExists($attributes['email'])) {
            return false;
        }
        $userClass = Yii::$app->classes['User'];
        $user = new $userClass();
        $user->scenario = 'creation';
        $user->status = 1;
        $user->password = md5(uniqid());
        $identityConfig = isset($attributes['primaryIdentity']) ? $attributes['primaryIdentity'] : [];
        $user->primaryIdentity = [
            'identity_provider_id' => $this->identityProvider->object->primaryKey,
            'meta' => $identityConfig,
        ];
        unset($attributes['primaryIdentity']);
        unset($attributes['_']);
        $user->attributes = $attributes;
        if (!$user->save()) {
            return false;
        }

        return $user;
    }
    public function userExists($email)
    {
        $userClass = Yii::$app->classes['User'];

        return $userClass::find()->disableAccessCheck()->where(['email' => $email])->count() > 0;
    }
}
