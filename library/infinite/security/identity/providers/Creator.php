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
 * Creator [[@doctodo class_description:infinite\security\identity\providers\Creator]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Creator extends \infinite\base\Component implements CreatorInterface
{
    /**
     * @var [[@doctodo var_type:_identityProvider]] [[@doctodo var_description:_identityProvider]]
     */
    protected $_identityProvider;
    /**
     * @var [[@doctodo var_type:priority]] [[@doctodo var_description:priority]]
     */
    public $priority = 0;
    /**
     * @var [[@doctodo var_type:usernameFilter]] [[@doctodo var_description:usernameFilter]]
     */
    public $usernameFilter = false;

    /**
     * Get identity provider.
     *
     * @return [[@doctodo return_type:getIdentityProvider]] [[@doctodo return_description:getIdentityProvider]]
     */
    public function getIdentityProvider()
    {
        return $this->_identityProvider;
    }

    /**
     * Set identity provider.
     */
    public function setIdentityProvider($idp)
    {
        $this->_identityProvider = $idp;
    }

    /**
     * [[@doctodo method_description:attemptCreate]].
     *
     * @return [[@doctodo return_type:attemptCreate]] [[@doctodo return_description:attemptCreate]]
     */
    public function attemptCreate($username, $password)
    {
        $username = $this->getUsername($username);

        return $this->internalAttemptCreate($username, $password);
    }

    /**
     * [[@doctodo method_description:internalAttemptCreate]].
     */
    abstract protected function internalAttemptCreate($username, $password);

    /**
     * Get username.
     *
     * @return [[@doctodo return_type:getUsername]] [[@doctodo return_description:getUsername]]
     */
    public function getUsername($username)
    {
        if ($this->usernameFilter) {
            return call_user_func($this->usernameFilter, $username);
        }

        return $username;
    }

    /**
     * [[@doctodo method_description:createUser]].
     *
     * @return [[@doctodo return_type:createUser]] [[@doctodo return_description:createUser]]
     */
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
    /**
     * [[@doctodo method_description:userExists]].
     *
     * @return [[@doctodo return_type:userExists]] [[@doctodo return_description:userExists]]
     */
    public function userExists($email)
    {
        $userClass = Yii::$app->classes['User'];

        return $userClass::find()->disableAccessCheck()->where(['email' => $email])->count() > 0;
    }
}
