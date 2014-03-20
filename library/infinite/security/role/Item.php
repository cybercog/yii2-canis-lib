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
	public $conflictRole = false;
	public $name;
	public $level = 100;
}
