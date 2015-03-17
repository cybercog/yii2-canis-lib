<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\security\identity\providers;

/**
 * LDAP [[@doctodo class_description:canis\security\identity\providers\LDAP]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LDAP extends \canis\security\identity\providers\Handler
{
    /**
     * @var [[@doctodo var_type:map]] [[@doctodo var_description:map]]
     */
    public $map;
    /**
     * @var [[@doctodo var_type:search]] [[@doctodo var_description:search]]
     */
    public $search = '';
    /**
     * @var [[@doctodo var_type:filter]] [[@doctodo var_description:filter]]
     */
    public $filter = '(&(objectCategory=person)(anr={username}))';
    /**
     * @var [[@doctodo var_type:_ldap]] [[@doctodo var_description:_ldap]]
     */
    protected $_ldap = false;

    /**
     * @inheritdoc
     */
    public function validatePassword($username, $password)
    {
        if (!isset($this->meta['username'])) {
            $this->meta['username'] = $username;
        }
        if (!$this->validConfig) {
            return false;
        }
        $username = $ousername = $this->meta['username'];
        if (isset($this->config['domain']) && strpos($username, '\\') === false) {
            $username = $this->config['domain'] . '\\' . $username;
        }
        $ldapAuth = $this->ldapAuth($username, $password);
        if ($ldapAuth) {
            $this->serverMeta = $this->getAttributes($ousername);
        }

        return $ldapAuth;
    }

    /**
     * [[@doctodo method_description:extractOus]].
     *
     * @param [[@doctodo param_type:dns]] $dns [[@doctodo param_description:dns]]
     *
     * @return [[@doctodo return_type:extractOus]] [[@doctodo return_description:extractOus]]
     */
    protected function extractOus($dns)
    {
        $ous = [];
        foreach ($dns as $dn) {
            foreach (explode(',', $dn) as $part) {
                if (substr($part, 0, 3) === 'OU=') {
                    $ous[] = substr($part, 3);
                }
            }
        }

        return $ous;
    }

    /**
     * [[@doctodo method_description:extractMembership]].
     *
     * @param [[@doctodo param_type:cns]] $cns [[@doctodo param_description:cns]]
     *
     * @return [[@doctodo return_type:extractMembership]] [[@doctodo return_description:extractMembership]]
     */
    protected function extractMembership($cns)
    {
        $memberships = [];
        foreach ($cns as $cn) {
            foreach (explode(',', $cn) as $part) {
                if (substr($part, 0, 3) === 'CN=') {
                    $memberships[] = substr($part, 3);
                }
            }
        }

        return $memberships;
    }

    /**
     * Get valid config.
     *
     * @return [[@doctodo return_type:getValidConfig]] [[@doctodo return_description:getValidConfig]]
     */
    public function getValidConfig()
    {
        if (!isset($this->config['hostname'])) {
            return false;
        }
        if (!isset($this->meta['username'])) {
            return false;
        }

        return true;
    }

    /**
     * [[@doctodo method_description:ldapAuth]].
     *
     * @param [[@doctodo param_type:username]] $username [[@doctodo param_description:username]]
     * @param [[@doctodo param_type:password]] $password [[@doctodo param_description:password]]
     *
     * @return [[@doctodo return_type:ldapAuth]] [[@doctodo return_description:ldapAuth]]
     */
    public function ldapAuth($username, $password)
    {
        $ldap = ldap_connect($this->config['hostname']);
        if (!empty($this->config['options'])) {
            foreach ($this->config['options'] as $key => $value) {
                ldap_set_option($ldap, $key, $value);
            }
        }
        if (!$ldap) {
            $this->addError('_', 'Unable to connect to authentication server.');

            return false;
        }
        if (!@ldap_bind($ldap, $username, $password)) {
            $this->addError('password', 'Incorrect username or password.');

            return false;
        }
        $this->_ldap = $ldap;

        return true;
    }

    /**
     * Get attributes.
     *
     * @param [[@doctodo param_type:username]] $username [[@doctodo param_description:username]]
     *
     * @return [[@doctodo return_type:getAttributes]] [[@doctodo return_description:getAttributes]]
     */
    public function getAttributes($username)
    {
        if (!$this->_ldap) {
            return false;
        }
        if (empty($this->map)) {
            return false;
        }
        $ldap = $this->_ldap;
        $attributes = [];
        $filter = str_replace('{username}', $username, $this->filter);
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
        $results['distinguishedName'] = isset($results['distinguishedName']) ? $results['distinguishedName'] : [];
        $results['memberOf'] = isset($results['memberOf']) ? $results['memberOf'] : [];
        $attributes['_']['ous'] = $this->extractOus($results['distinguishedName']);
        $attributes['_']['memberOf'] = $this->extractMembership($results['memberOf']);
        foreach ($this->map as $key => $field) {
            $filter = function ($value) { return $value; };
            if (is_array($field)) {
                if (isset($field['filter'])) {
                    $filter = $field['filter'];
                }
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
    /**
     * Get ldap.
     *
     * @return [[@doctodo return_type:getLdap]] [[@doctodo return_description:getLdap]]
     */
    public function getLdap()
    {
        return $this->_ldap;
    }
}
