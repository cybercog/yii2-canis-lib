<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\mongodb;

use teal\base\ComponentTrait;
use Yii;

/**
 * ActiveRecord [[@doctodo class_description:teal\db\mongodb\ActiveRecord]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveRecord extends \yii\mongodb\ActiveRecord
{
    use ComponentTrait;

    /**
     * @inheritdoc
     */
    public static function getCollection()
    {
        $collectionName = static::collectionName();
        $collectionNames = static::getDb()->database->getCollectionNames();
        $collection = static::getDb()->database->getCollection($collectionName);
        if (!in_array($collectionName, $collectionNames)) {
            static::prepareNewCollection($collection);
        }

        return $collection;
    }

    /**
     * [[@doctodo method_description:collectionExists]].
     *
     * @return [[@doctodo return_type:collectionExists]] [[@doctodo return_description:collectionExists]]
     */
    public static function collectionExists()
    {
        $collectionName = static::collectionName();
        $collectionNames = static::getDb()->database->getCollectionNames();
        if (in_array($collectionName, $collectionNames)) {
            return true;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:prepareNewCollection]].
     *
     * @param [[@doctodo param_type:collection]] $collection [[@doctodo param_description:collection]]
     */
    public static function prepareNewCollection($collection)
    {
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }
}
