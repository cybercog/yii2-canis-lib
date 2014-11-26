<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

trait QueryTrait
{
	public function cursor($db = null)
	{
		return $this->buildCursor($db);
	}
}
