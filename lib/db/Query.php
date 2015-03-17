<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

use canis\base\ComponentTrait;

/**
 * Query [[@doctodo class_description:canis\db\Query]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Query extends \yii\db\Query
{
    const EVENT_BEFORE_QUERY = 'beforeQuery';

    use QueryTrait;
    use ComponentTrait;
}
