<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

use yii\base\InvalidConfigException;
/**
 * Ldap [@doctodo write class description for Ldap]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LdapCreator extends Creator
{
	public $map;
	public $search = '';
	public $filter = '(&(objectCategory=person)(anr={username}))';

	protected function internalAttemptCreate($username, $password) {
		$handler = $this->identityProvider->getHandler(null);
		if (!($handler instanceof Ldap)) {
			throw new InvalidConfigException("Identity provider creator for LDAP must be for the LDAP handler");
		}
		if ($handler->validatePassword($username, $password) && $handler->ldap) {
			$attributes = $this->getAttributes($handler->ldap, $username);
			return $this->createUser($attributes);
		}
		return false;
	}

	public function getAttributes($ldap, $username)
	{
		if (empty($this->map)) { return false; }
		$attr = array();
		$filter = str_replace( '{username}', $username, $this->filter);
		$sr = ldap_search($ldap, $this->search, $filter, array_keys($this->map)); 
		$info = ldap_get_entries($ldap, $sr);
		if (empty($info['count'])) {
			return false;
		}
		$results = ldap_get_attributes($ldap, ldap_first_entry($ldap, $sr));
		if (empty($results)) {
			return false;
		}
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
}
