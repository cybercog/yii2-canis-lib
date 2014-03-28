<?php
/**
 * library/security/role/Role.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security\role;

use Yii;

use infinite\base\exceptions\Exception;

class Item extends \infinite\base\collector\Item 
{
	public $exclusive = false;
	public $name;
	public $level = 100;

	public function getPackage()
	{
		return [
			'id' => $this->id,
			'system_id' => $this->object->system_id,
			'label' => $this->name,
			'exclusive' => $this->exclusive,
			'level' => $this->level,
		];
	}

	public function getId()
	{
		return $this->object->primaryKey;
	}

	public function getSystemId()
	{
		return $this->object->system_id;
	}

	public function getLevelSection()
	{
		if ($this->level > INFINITE_ROLE_LEVEL_MANAGER) {
			return 'owner';
		} elseif ($this->level > INFINITE_ROLE_LEVEL_EDITOR) {
			return 'manager';
		} elseif ($this->level > INFINTE_ROLE_LEVEL_COMMENTER) {
			return 'editor';
		} elseif ($this->level > INFINITE_ROLE_LEVEL_VIEWER) {
			return 'commenter';
		} elseif ($this->level > 0) {
			return 'viewer';
		}
		return 'none';
	}
}
