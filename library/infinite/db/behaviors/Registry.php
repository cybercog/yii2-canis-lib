<?php
/**
 * library/db/behaviors/Registry.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;

use yii\db\Expression;
use infinite\base\Exception;

class Registry extends \infinite\db\behaviors\ActiveRecord
{
    public $registryClass = 'app\\models\\Registry';
    public static $_table;
    public $objectOwner;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => 'afterSaveFail'
        ];
    }

    public function getTable()
    {
        if (is_null(self::$_table)) {
            $_registryModel = $this->registryClass;
            $r = new $_registryModel;
            self::$_table = $r->tableName();
        }
        return self::$_table;
    }

    public function beforeInsert($event)
    {
        if ($this->owner->isNewRecord && $this->owner->primaryKey == NULL) {
            $_registryModel = $this->registryClass;
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
        return self::generateUuid(get_class($this->owner));
    }


    /**
     *
     *
     * @param unknown $model (optional)
     * @return unknown
     */
    public static function generateUuid($model = NULL)
    {
        $model = strtoupper(sha1($model));
        $salt = strtoupper(sha1(Yii::$app->params['salt']));
        return sprintf('%s-%s-%04X-%04X-%04X%04X%04X',
            substr($model, 0, 8),
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
     * @param unknown $event
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
        $pk = $this->owner->primaryKey;
        if (empty($pk)) {
            return true;
        }

        $_registryModel = $this->registryClass;
        $_registry = $_registryModel::find()->pk($pk)->one();
        if (!empty($_registry)) {
            return $_registry->delete();
        }
        return true;
    }
}
