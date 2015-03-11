<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\mongodb;

use teal\base\ComponentTrait;

/**
 * ActiveQuery [[@doctodo class_description:teal\db\mongodb\ActiveQuery]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveQuery extends \yii\mongodb\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
}
