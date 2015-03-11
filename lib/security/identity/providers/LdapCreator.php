<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security\identity\providers;

/**
 * LdapCreator [[@doctodo class_description:teal\security\identity\providers\LdapCreator]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LdapCreator extends Creator
{
    /**
     * @inheritdoc
     */
    protected function internalAttemptCreate($username, $password)
    {
        $handler = $this->identityProvider->getHandler(null);
        if ($handler->validatePassword($username, $password) && $handler->ldap) {
            $attributes = $handler->serverMeta;

            return $this->createUser($attributes);
        }

        return false;
    }
}
