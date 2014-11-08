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
	public $map;
	public $search = '';
	public $filter = '(&(objectCategory=person)(anr={username}))';
	protected $_ldap = false;

	public function validatePassword($username, $password)
	{
		if (!isset($this->meta['username'])) {
			$this->meta['username'] = $username;
		}
		if (!$this->validConfig) { return false; }
		$username = $ousername = $this->meta['username'];
		if (isset($this->config['domain']) && strpos($username, '\\') === false) {
			$username = $this->config['domain'] .'\\'. $username;
		}
		$ldapAuth = $this->ldapAuth($username, $password);
		if ($ldapAuth) {
			$this->serverMeta = $this->getAttributes($ousername);
		}
		return $ldapAuth;
	}

	protected function extractOus($dns)
    {
        $ous = [];
        foreach ($dns as $dn) {
	        foreach(explode(',', $dn) as $part) {
	            if (substr($part, 0, 3) === 'OU=') {
	                $ous[] = substr($part, 3);
	            }
	        }
	    }
        return $ous;
    }


	protected function extractMembership($cns)
    {
        $memberships = [];
        foreach ($cns as $cn) {
		    foreach(explode(',', $cn) as $part) {
		        if (substr($part, 0, 3) === 'CN=') {
		            $memberships[] = substr($part, 3);
		        }
		    }
		}
        return $memberships;
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
        	$this->addError('password', 'Incorrect username or password.');
        	return false;
        }
        $this->_ldap = $ldap;
        return true;
    }


	public function getAttributes($username)
	{
		if (!$this->_ldap) { return false; }
		if (empty($this->map)) { return false; }
		$ldap = $this->_ldap;
		$attributes = [];
		$filter = str_replace( '{username}', $username, $this->filter);
		$sr = ldap_search($ldap, $this->search, $filter, array_merge(['distinguishedName', 'memberOf'], array_keys($this->map))); 
		$info = ldap_get_entries($ldap, $sr);
		if (empty($info['count'])) {
			return false;
		}
		$results = ldap_get_attributes($ldap, ldap_first_entry($ldap, $sr));
		if (empty($results)) {
			return false;
		}
		$attributes['_'] = [];
		$attributes['_']['ous'] = $this->extractOus($results['distinguishedName']);
		$attributes['_']['memberOf'] = $this->extractMembership($results['memberOf']);
		foreach ($this->map as $key => $field) {
			$filter = function($value) { return $value; };
			if (is_array($field)) {
				if (isset($field['filter'])) { $filter = $field['filter']; }
				$field = $field['field'];
			}
			$value = null;
			if (isset($results[$key][0])) {
				$value = $results[$key][0];
			}
			$value = $filter($value);
			if (strpos($field, '.') !== false) {
				$fieldParts = explode('.', $field);
				if (!isset($attributes[$fieldParts[0]])) {
					$attributes[$fieldParts[0]] = [];
				}
				$attributes[$fieldParts[0]][$fieldParts[1]] = $value;
			} else {
				$attributes[$field] = $value;
			}
		}
		return $attributes;
	}
    public function getLdap()
    {
    	return $this->_ldap;
    }
}
