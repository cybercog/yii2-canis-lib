<?php
/**
 * library/db/behaviors/Blame.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

class Blame extends \infinite\db\behaviors\ActiveRecord {
	public function events()
	{
		return [
			\infinite\db\ActiveQuery::EVENT_BEFORE_INSERT => 'beforeSave',
			\infinite\db\ActiveQuery::EVENT_BEFORE_UPDATE => 'beforeSave',
		];
	}
}


?>
