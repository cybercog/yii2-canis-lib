<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

/**
 * Ldap [@doctodo write class description for Ldap]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Ldap extends \infinite\security\identity\providers\Handler
{
	protected $_ldap = false;
	public function validatePassword($username, $password)
	{
		if (!isset($this->meta['username'])) {
			$this->meta['username'] = $username;
		}
		if (!$this->validConfig) { return false; }
		$username = $this->meta['username'];
		if (isset($this->config['domain']) && strpos($username, '\\') === false) {
			$username = $this->config['domain'] .'\\'. $username;
		}

		return $this->ldapAuth($username, $password);
	}

	public function getValidConfig()
	{
		if (!isset($this->config['hostname'])) { return false; }
		if (!isset($this->meta['username'])) { return false; }
		return true;
	}

	public function ldapAuth($username, $password)
	{
        $ldap = ldap_connect($this->config['hostname']);
		if (!empty($this->config['options'])) {
			foreach ($this->config['options'] as $key => $value) {
				ldap_set_option($ldap, $key, $value);
			}
		}
        if(!$ldap){
        	$this->addError('_', 'Unable to connect to authentication server.');
        	return false;
        }
        if (!@ldap_bind($ldap, $username, $password)){
        	$this->addError('password', 'Incorrect username or password.kj');
        	return false;
        }
        $this->_ldap = $ldap;
        return true;
    }

    public function getLdap()
    {
    	return $this->_ldap;
    }
}
