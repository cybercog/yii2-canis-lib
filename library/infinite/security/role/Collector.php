<?php
/**
 * library/security/role/Engine.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security\role;

use infinite\helpers\ArrayHelper;

class Collector extends \infinite\base\collector\Collector
{
    public $initial = [];

    public function getCollectorItemClass() {
		return 'infinite\security\role\Item';
	}


}
