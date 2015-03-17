<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\mongodb;

use canis\base\ComponentTrait;

/**
 * ActiveQuery [[@doctodo class_description:canis\db\mongodb\ActiveQuery]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveQuery extends \yii\mongodb\ActiveQuery
{
    use ComponentTrait;
    use QueryTrait;
}
