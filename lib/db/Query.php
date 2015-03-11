<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db;

use teal\base\ComponentTrait;

/**
 * Query [[@doctodo class_description:teal\db\Query]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Query extends \yii\db\Query
{
    const EVENT_BEFORE_QUERY = 'beforeQuery';

    use QueryTrait;
    use ComponentTrait;
}
