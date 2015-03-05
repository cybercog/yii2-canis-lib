<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use infinite\base\ComponentTrait;

/**
 * ActiveQuery [[@doctodo class_description:infinite\db\mongodb\ActiveQuery]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveQuery extends \yii\mongodb\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
}
