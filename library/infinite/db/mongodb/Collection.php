<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\mongodb;

use Yii;
use yii\mongodb\Exception;

use infinite\base\ComponentTrait;

class Collection extends \yii\mongodb\Collection
{
    use ComponentTrait;
 	
 	public function getDb()
 	{
 		return Yii::$app->mongodb;
 	}

    /**
     * Drops this collection.
     * @throws Exception on failure.
     * @return boolean whether the operation successful.
     */
    public function rename($newName)
    {
        $token = $this->composeLogToken('rename');
        Yii::info($token, __METHOD__);
        try {
            Yii::beginProfile($token, __METHOD__);
            $mongo = $this->db->mongoClient;
    		$query = array("renameCollection" => $this->db->database->name . '.'. $this->name, "to" => $this->db->database->name . '.'. $newName, "dropTarget" => "true");
    		$result = $mongo->admin->command($query);
            $this->tryResultError($result);
            Yii::endProfile($token, __METHOD__);

            return true;
        } catch (\Exception $e) {
            Yii::endProfile($token, __METHOD__);
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
