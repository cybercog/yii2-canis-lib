<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

use infinite\base\ComponentTrait;

class Query extends \yii\db\Query
{
    const EVENT_BEFORE_QUERY = 'beforeQuery';

    use QueryTrait;
    use ComponentTrait;
}
