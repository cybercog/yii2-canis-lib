<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\mongodb;

trait QueryTrait
{
    public function cursor($db = null)
    {
        return $this->buildCursor($db);
    }
}
