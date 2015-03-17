<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\mongodb;

trait QueryTrait
{
    public function cursor($db = null)
    {
        return $this->buildCursor($db);
    }
}
