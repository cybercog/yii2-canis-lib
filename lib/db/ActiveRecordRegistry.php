<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

/**
 * ActiveRecordRegistry is the model class for table "{{%active_record_registry}}".
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveRecordRegistry extends ActiveRecord
{
    use ActiveRecordRegistryTrait;
}
