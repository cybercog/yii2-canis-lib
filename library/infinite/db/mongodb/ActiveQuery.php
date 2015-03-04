<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use infinite\base\ComponentTrait;

class ActiveQuery extends \yii\mongodb\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
}
