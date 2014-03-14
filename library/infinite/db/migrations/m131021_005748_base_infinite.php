<?php

namespace infinite\db\migrations;

class m131021_005748_base_infinite extends \infinite\db\Migration
{
    public function up()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();

        // aca
        $this->dropExistingTable('aca');
        
        $this->createTable('aca', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'name' => 'string NOT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->addForeignKey('acaRegistry', 'aca', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');

        // acl
        $this->dropExistingTable('acl');
        
        $this->createTable('acl', [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'acl_role_id' => 'bigint unsigned DEFAULT NULL',
            'accessing_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'controlled_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'aca_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'object_model' => 'string DEFAULT NULL',
            'access' => 'tinyint(4) DEFAULT NULL', // -1 explicitly deny access (rare); 1 allow access; 2 inherit from parent
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('aclAccessingObject', 'acl', 'accessing_object_id', false);
        $this->createIndex('aclControlledObject', 'acl', 'controlled_object_id', false);
        $this->createIndex('aclAcaRegistry', 'acl', 'aca_id', false);
        $this->createIndex('aclModel', 'acl', 'object_model', false);
        $this->createIndex('aclCombo', 'acl', 'accessing_object_id,controlled_object_id,object_model', false);
        $this->createIndex('aclComboAccess', 'acl', 'accessing_object_id,controlled_object_id,object_model,access', false);
        $this->createIndex('aclComboAca', 'acl', 'accessing_object_id,controlled_object_id,aca_id,object_model', false);
        $this->createIndex('aclComboAcaAccess', 'acl', 'accessing_object_id,controlled_object_id,aca_id,object_model,access', false);
        $this->createIndex('aclAclRole', 'acl', 'acl_role_id', false);
        $this->addForeignKey('aclAclRole', 'acl', 'acl_role_id', 'acl_role', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('aclAccessingObject', 'acl', 'accessing_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('aclControlledObject', 'acl', 'controlled_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // acl_role
        $this->dropExistingTable('acl_role');
        
        $this->createTable('acl_role', [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'accessing_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'controlled_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'role_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('aclRoleAccessingObject', 'acl_role', 'accessing_object_id', false);
        $this->createIndex('aclRpleControlledObject', 'acl_role', 'controlled_object_id', false);
        $this->createIndex('aclRoleRole', 'acl_role', 'role_id', false);
        $this->createIndex('aclRolePrimary', 'acl_role', 'accessing_object_id,controlled_object_id', false);
        $this->createIndex('aclRoleCombo', 'acl_role', 'accessing_object_id,controlled_object_id,role_id', false);
        $this->addForeignKey('aclRoleAccessingObject', 'acl_role', 'accessing_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('aclRoleControlledObject', 'acl_role', 'controlled_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('aclRoleRole', 'acl_role', 'role_id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // group
        $this->dropExistingTable('group');
        
        $this->createTable('group', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'name' => 'string NOT NULL',
            'system' => 'string DEFAULT NULL',
            'level' => 'integer NOT NULL DEFAULT 0',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->addForeignKey('groupRegistry', 'group', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // http_session
        // $this->dropExistingTable('http_session');
        
        // $this->createTable('http_session', [
        //     'id' => 'string NOT NULL PRIMARY KEY',
        //     'expire' => 'integer DEFAULT NULL',
        //     'data' => 'text DEFAULT NULL'
        // ]);

        // $this->createIndex('httpSessionExpire', 'http_session', 'expire', false);


        // identity_provider
        $this->dropExistingTable('identity_provider');
        
        $this->createTable('identity_provider', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'name' => 'string DEFAULT NULL',
            'meta' => 'blob DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->addForeignKey('idpRegistry', 'identity_provider', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // identity
        $this->dropExistingTable('identity');
        
        $this->createTable('identity', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'user_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'identity_provider_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL',
            'token' => 'text DEFAULT NULL',
            'meta' => 'blob DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('identityIdp', 'identity', 'identity_provider_id', false);
        $this->addForeignKey('identityRegistry', 'identity', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('identityUser', 'identity', 'id', 'user', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('identityIdp', 'identity', 'identity_provider_id', 'identity_provider', 'id', 'CASCADE', 'CASCADE');


        // registry
        $this->dropExistingTable('registry');
        
        $this->createTable('registry', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'owner_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'object_model' => 'string DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('registryIndex', 'registry', 'id,object_model', false);


        // relation
        $this->dropExistingTable('relation');
        
        $this->createTable('relation', [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'parent_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT \'\'',
            'child_object_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT \'\'',
            'start' => 'date DEFAULT NULL',
            'end' => 'date DEFAULT NULL',
            'active' => 'bool NOT NULL',
            'primary' => 'bool NOT NULL DEFAULT 0',
            'special' => 'string DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('relationParentChild', 'relation', 'parent_object_id,child_object_id', true);
        $this->createIndex('relationParent', 'relation', 'parent_object_id', false);
        $this->createIndex('relationChild', 'relation', 'child_object_id', false);
        $this->createIndex('relationCommonCombo', 'relation', 'parent_object_id,child_object_id,start,end,active', false);
        $this->addForeignKey('relationChildRegistry', 'relation', 'child_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('relationParentRegistry', 'relation', 'parent_object_id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // role
        $this->dropExistingTable('role');
        
        $this->createTable('role', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'name' => 'string DEFAULT NULL',
            'system_id' => 'string NOT NULL DEFAULT \'\'',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);

        $this->createIndex('roleName', 'role', 'system_id', false);
        $this->addForeignKey('roleRegistry', 'role', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');


        // user
        $this->dropExistingTable('user');
        
        $this->createTable('user', [
            'id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY',
            'primary_identity_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'object_individual_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'first_name' => 'string DEFAULT NULL',
            'last_name' => 'string DEFAULT NULL',
            'email' => 'string DEFAULT NULL',
            'username' => 'string NOT NULL',
            'password_hash' => 'string DEFAULT NULL',
            'password_reset_token' => 'string DEFAULT NULL',
            'auth_key' => 'string(32) NOT NULL',
            'status' => 'tinyint(4) NOT NULL DEFAULT 0',
            'last_login' => 'datetime DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL',
            'created_user_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL',
            'modified_user_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'deleted' => 'datetime DEFAULT NULL',
            'deleted_user_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL'
        ]);

        $this->createIndex('userIdentityPrivider', 'user', 'primary_identity_id', false);
        $this->createIndex('userIndividual', 'user', 'object_individual_id', false);
        $this->addForeignKey('userIndividual', 'user', 'object_individual_id', 'object_individual', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('userIdentity', 'user', 'primary_identity_id', 'identity', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('userRegistry', 'user', 'id', 'registry', 'id', 'CASCADE', 'CASCADE');

        $this->db->createCommand()->checkIntegrity(true)->execute();

    }



    public function down()
    {
      $this->db->createCommand()->checkIntegrity(false)->execute();

      $this->dropExistingTable('aca');
      $this->dropExistingTable('acl');
      $this->dropExistingTable('acl_role');
      $this->dropExistingTable('group');
      $this->dropExistingTable('http_session');
      $this->dropExistingTable('identity_provider');
      $this->dropExistingTable('identity');
      $this->dropExistingTable('registry');
      $this->dropExistingTable('relation');
      $this->dropExistingTable('role');
      $this->dropExistingTable('user');

      $this->db->createCommand()->checkIntegrity(true)->execute();
      return true;
    }
}
