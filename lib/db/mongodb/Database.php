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
 * Database [[@doctodo class_description:teal\db\mongodb\Database]].
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
