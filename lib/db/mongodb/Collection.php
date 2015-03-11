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
use yii\mongodb\Exception;

/**
 * Collection [[@doctodo class_description:teal\db\mongodb\Collection]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Collection extends \yii\mongodb\Collection
{
    use ComponentTrait;

    /**
     * Get db.
     *
     * @return [[@doctodo return_type:getDb]] [[@doctodo return_description:getDb]]
     */
    public function getDb()
    {
        return Yii::$app->mongodb;
    }

    /**
     * Drops this collection.
     *
     * @param [[@doctodo param_type:newName]] $newName [[@doctodo param_description:newName]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return boolean whether the operation successful.
     *
     */
    public function rename($newName)
    {
        $token = $this->composeLogToken('rename');
        Yii::info($token, __METHOD__);
        try {
            Yii::beginProfile($token, __METHOD__);
            $mongo = $this->db->mongoClient;
            $query = [
                "renameCollection" => $this->db->database->name . '.' . $this->name,
                "to" => $this->db->database->name . '.' . $newName,
                "dropTarget" => "true",
            ];
            $options = [
                "socketTimeoutMS" => -1,
            ];
            $result = $mongo->admin->command($query, $options);
            $this->tryResultError($result);
            Yii::endProfile($token, __METHOD__);

            return true;
        } catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
