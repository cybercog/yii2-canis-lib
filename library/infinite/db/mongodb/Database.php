<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use infinite\base\ComponentTrait;
use Yii;

/**
 * Database [[@doctodo class_description:infinite\db\mongodb\Database]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Database extends \yii\mongodb\Database
{
    use ComponentTrait;

    /**
     * Get collection names.
     *
     * @return [[@doctodo return_type:getCollectionNames]] [[@doctodo return_description:getCollectionNames]]
     */
    public function getCollectionNames()
    {
        return $this->mongoDb->getCollectionNames();
    }
    /**
     * @inheritdoc
     */
    protected function selectCollection($name)
    {
        return Yii::createObject([
            'class' => Collection::className(),
            'mongoCollection' => $this->mongoDb->selectCollection($name),
        ]);
    }
}
