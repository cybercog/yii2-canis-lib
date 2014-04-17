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

/**
 * Registry [@doctodo write class description for Registry]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Registry extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var__table_type__ __var__table_description__
     */
    public static $_table;
    /**
     * @var __var__model_type__ __var__model_description__
     */
    protected $_model;

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => 'afterSaveFail'
        ];
    }

    /**
    * @inheritdoc
     */
    public function safeAttributes()
    {
        return [];
    }

    /**
     * Get registry model
     * @return __return_getRegistryModel_type__ __return_getRegistryModel_description__
     */
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

    /**
     * Get table
     * @return __return_getTable_type__ __return_getTable_description__
     */
    public function getTable()
    {
        if (is_null(self::$_table)) {
            $_registryModel = Yii::$app->classes['Registry'];
            self::$_table = $_registryModel::tableName();
        }

        return self::$_table;
    }

    /**
     * __method_beforeInsert_description__
     * @param __param_event_type__ $event __param_event_description__
     * @throws Exception __exception_Exception_description__
     */
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

    /**
     * __method_uuid_description__
     * @return __return_uuid_type__ __return_uuid_description__
     */
    public function uuid()
    {
        $ownerClass = get_class($this->owner);

        return self::generateUuid($ownerClass::modelPrefix());
    }

    /**
     * __method_generateUuid_description__
     * @param __param_modelPrefix_type__ $modelPrefix __param_modelPrefix_description__
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
     * __method_afterSaveFail_description__
     * @param unknown $event
     */
    public function afterSaveFail($event)
    {
        if ($this->owner->isNewRecord and !$this->owner->checkExistence()) {
            $this->_deleteRegistry();
        }
    }

    /**
     * __method_afterDelete_description__
     * @param unknown $event
     * @return unknown
     */
    public function afterDelete($event)
    {
        return $this->_deleteRegistry();
    }

    /**
     * __method__deleteRegistry_description__
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
