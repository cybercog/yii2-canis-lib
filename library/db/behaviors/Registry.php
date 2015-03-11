<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors;

use teal\base\exceptions\Exception;
use Yii;
use yii\db\Expression;

/**
 * Registry [[@doctodo class_description:teal\db\behaviors\Registry]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Registry extends \teal\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:_table]] [[@doctodo var_description:_table]]
     */
    public static $_table;
    /**
     * @var [[@doctodo var_type:_model]] [[@doctodo var_description:_model]]
     */
    protected $_model;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \teal\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \teal\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            \teal\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => 'afterSaveFail',
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
     * Get registry model.
     *
     * @return [[@doctodo return_type:getRegistryModel]] [[@doctodo return_description:getRegistryModel]]
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
     * Get table.
     *
     * @return [[@doctodo return_type:getTable]] [[@doctodo return_description:getTable]]
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
     * [[@doctodo method_description:beforeInsert]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     */
    public function beforeInsert($event)
    {
        if ($this->owner->isNewRecord && $this->owner->primaryKey == null) {
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
     * [[@doctodo method_description:uuid]].
     *
     * @return [[@doctodo return_type:uuid]] [[@doctodo return_description:uuid]]
     */
    public function uuid()
    {
        $ownerClass = get_class($this->owner);

        return self::generateUuid($ownerClass::modelPrefix());
    }

    /**
     * [[@doctodo method_description:generateUuid]].
     *
     * @param [[@doctodo param_type:modelPrefix]] $modelPrefix [[@doctodo param_description:modelPrefix]]
     *
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
     * [[@doctodo method_description:afterSaveFail]].
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
     * [[@doctodo method_description:afterDelete]].
     *
     * @param unknown $event
     *
     * @return unknown
     */
    public function afterDelete($event)
    {
        return $this->_deleteRegistry();
    }

    /**
     * [[@doctodo method_description:_deleteRegistry]].
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
