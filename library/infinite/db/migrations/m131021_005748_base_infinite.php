<?php

class m131021_005748_base_infinite extends \yii\db\Migration
{
	public function up()
	{
		$sql = <<< END
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table aca
# ------------------------------------------------------------

DROP TABLE IF EXISTS `aca`;

CREATE TABLE `aca` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `acaRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table acl
# ------------------------------------------------------------

DROP TABLE IF EXISTS `acl`;

CREATE TABLE `acl` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `acl_role_id` bigint(20) unsigned DEFAULT NULL,
  `accessing_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controlled_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `aca_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `object_model` varchar(255) DEFAULT NULL,
  `access` tinyint(1) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aclAccessingObject` (`accessing_object_id`),
  KEY `aclControlledObject` (`controlled_object_id`),
  KEY `aclAcaRegistry` (`aca_id`),
  KEY `aclModel` (`object_model`),
  KEY `aclCombo` (`accessing_object_id`,`controlled_object_id`,`object_model`),
  KEY `aclComboAccess` (`accessing_object_id`,`controlled_object_id`,`object_model`,`access`),
  KEY `aclComboAca` (`accessing_object_id`,`controlled_object_id`,`aca_id`,`object_model`),
  KEY `aclComboAcaAccess` (`accessing_object_id`,`controlled_object_id`,`aca_id`,`object_model`,`access`),
  KEY `aclAclRole` (`acl_role_id`),
  CONSTRAINT `aclAclRole` FOREIGN KEY (`acl_role_id`) REFERENCES `acl_role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `aclAccessingObject` FOREIGN KEY (`accessing_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `aclControlledObject` FOREIGN KEY (`controlled_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table acl_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `acl_role`;

CREATE TABLE `acl_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accessing_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controlled_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `role_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aclRoleAccessingObject` (`accessing_object_id`),
  KEY `aclRpleControlledObject` (`controlled_object_id`),
  KEY `aclRoleRole` (`role_id`),
  KEY `aclRolePrimary` (`accessing_object_id`,`controlled_object_id`),
  KEY `aclRoleCombo` (`accessing_object_id`,`controlled_object_id`,`role_id`),
  CONSTRAINT `aclRoleAccessingObject` FOREIGN KEY (`accessing_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `aclRoleControlledObject` FOREIGN KEY (`controlled_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `aclRoleRole` FOREIGN KEY (`role_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `group`;

CREATE TABLE `group` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) NOT NULL,
  `system` varchar(20) DEFAULT NULL,
  `level` mediumint(9) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `groupRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table http_session
# ------------------------------------------------------------

DROP TABLE IF EXISTS `http_session`;

CREATE TABLE `http_session` (
  `id` char(32) NOT NULL,
  `expire` int(11) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`),
  KEY `ses_expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table identity_provider
# ------------------------------------------------------------

DROP TABLE IF EXISTS `identity_provider`;

CREATE TABLE `identity_provider` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `meta` BLOB DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `idpRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table identity
# ------------------------------------------------------------

DROP TABLE IF EXISTS `identity`;

CREATE TABLE `identity` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identity_provider_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `token` TEXT DEFAULT NULL,
  `meta` BLOB DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `identityRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `identityUser` FOREIGN KEY (`id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `identityIdp` FOREIGN KEY (`identity_provider_id`) REFERENCES `identity_provider` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table registry
# ------------------------------------------------------------

DROP TABLE IF EXISTS `registry`;

CREATE TABLE `registry` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `object_model` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registryIndex` (`id`,`object_model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table relation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `relation`;

CREATE TABLE `relation` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `child_object_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `special` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_object_id_2` (`parent_object_id`,`child_object_id`),
  KEY `parent_object_id` (`parent_object_id`),
  KEY `child_object_id` (`child_object_id`),
  KEY `type` (`parent_object_id`,`child_object_id`),
  KEY `parent_object_id_3` (`parent_object_id`),
  CONSTRAINT `relationChildRegistry` FOREIGN KEY (`child_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `relationParentRegistry` FOREIGN KEY (`parent_object_id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `system_id` varchar(100) NOT NULL DEFAULT '',
  `system_version` float unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roleName` (`system_id`),
  CONSTRAINT `roleRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `primary_identity_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `object_individual_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `is_administrator` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `created_user_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `modified_user_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ldap_id` (`ldap_id`),
  KEY `userIndividual` (`object_individual_id`),
  CONSTRAINT `userIndividual` FOREIGN KEY (`object_individual_id`) REFERENCES `object_individual` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `userIdentity` FOREIGN KEY (`primary_identity_id`) REFERENCES `identity` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `userRegistry` FOREIGN KEY (`id`) REFERENCES `registry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

END;
		return $this->execute($sql);
	}



	public function down()
	{
		echo "m131021_005748_base_infinite does not support migration down.\n";
		return false;
	}
}
