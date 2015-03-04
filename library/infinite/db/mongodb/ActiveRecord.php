<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use Yii;
use infinite\base\ComponentTrait;

class ActiveRecord extends \yii\mongodb\ActiveRecord
{
    use ComponentTrait;

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

    public static function collectionExists()
    {
        $collectionName = static::collectionName();
        $collectionNames = static::getDb()->database->getCollectionNames();
        if (in_array($collectionName, $collectionNames)) {
            return true;
        }

        return false;
    }

    public static function prepareNewCollection($collection)
    {
    }

    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }
}
