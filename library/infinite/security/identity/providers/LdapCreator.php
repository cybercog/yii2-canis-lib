<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

/**
 * Ldap [@doctodo write class description for Ldap].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LdapCreator extends Creator
{
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
