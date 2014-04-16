<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;

class Registry extends \infinite\db\behaviors\ActiveRecord
{
    public static $_table;
    protected $_model;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => 'afterSaveFail'
        ];
    }

    public function safeAttributes()
    {
        return [];
    }

    public function getRegistryModel()
    {
        if (!is_null($this->_model)) {
            return $this->_model;
        }
        if (empty($this->owner->primaryKey)) {
            return false;
        }
        $registryClass = Yii::$app->classes['Registry'];
        $registry = $registryClass::find()->disableAccessCheck()->pk($this->owner->primaryKey)->one();
        if (!empty($registry)) {
            return $registry;
        }

        return false;
    }

    public function getTable()
    {
        if (is_null(self::$_table)) {
            $_registryModel = Yii::$app->classes['Registry'];
            self::$_table = $_registryModel::tableName();
        }

        return self::$_table;
    }

    public function beforeInsert($event)
    {
        if ($this->owner->isNewRecord && $this->owner->primaryKey == NULL) {
            $_registryModel = Yii::$app->classes['Registry'];
            $fields = ['id' => $this->uuid(), 'object_model' => $this->owner->modelAlias, 'created' =>  new Expression('NOW()')];
            if (!Yii::$app->db->createCommand()->insert($this->table, $fields)->execute()) {
                throw new Exception("Unable to create registry item!");
            }
            $pk = $this->owner->primaryKey();
            $this->owner->{$pk[0]} = $fields['id'];
        }
    }

    public function uuid()
    {
        $ownerClass = get_class($this->owner);

        return self::generateUuid($ownerClass::modelPrefix());
    }

    /**
     *
     *
     * @param  unknown $model (optional)
     * @return unknown
     */
    public static function generateUuid($modelPrefix)
    {
        $salt = strtoupper(sha1(Yii::$app->params['salt']));

        return sprintf('%s-%s-%04X-%04X-%04X%04X%04X',
            $modelPrefix,
            substr($salt, 0, 4),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535));
    }

    /**
     *
     *
     * @param unknown $event
     */
    public function afterSaveFail($event)
    {
        if ($this->owner->isNewRecord and !$this->owner->checkExistence()) {
            $this->_deleteRegistry();
        }
    }

    /**
     *
     *
     * @param  unknown $event
     * @return unknown
     */
    public function afterDelete($event)
    {
        return $this->_deleteRegistry();
    }

    /**
     *
     *
     * @return unknown
     */
    protected function _deleteRegistry()
    {
        if (!empty($this->owner->registryModel)) {
           $this->owner->registryModel->delete();
        }

        return true;
    }
}
