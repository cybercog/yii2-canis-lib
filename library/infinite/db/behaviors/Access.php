<?php
/**
 * library/db/behaviors/Access.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

class Access extends \infinite\db\behaviors\ActiveRecord {
	public function events()
	{
		return [
			\infinite\db\ActiveQuery::EVENT_BEFORE_QUERY => 'beforeQuery',
		];
	}

	public function beforeQuery($query) {
		throw new \Exception("boom");
	}
}


?>
