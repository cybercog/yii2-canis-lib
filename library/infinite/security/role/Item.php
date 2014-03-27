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
}
