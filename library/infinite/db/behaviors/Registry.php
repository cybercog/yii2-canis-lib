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
    protected $_objectOwner;
    protected $_model;
    protected $_ownerDirty = false;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => 'afterSaveFail'
        ];
    }
    
    public function safeAttributes()
    {
        return ['objectOwner'];
    }

    public function hasOwner()
    {
        if (!empty($this->_objectOwner)) {
            return true;
        }

        $registryModel = $this->registryModel;
        if ($registryModel && !empty($registryModel->owner_id)) {
            return true;
        }
        return false;
    }

    public function setObjectOwner($owner)
    {
        if (is_object($owner)) {
            $ownerId = $owner->primaryKey;
        } else {
            $ownerId = $owner;
        }
        $this->_ownerDirty = true;
        if (!empty($this->_objectOwner) && $this->objectOwnerId === $ownerId) {
            $this->_ownerDirty = false;
        } elseif (is_null($this->_objectOwner)) {
            $registryModel = $this->registryModel;
            if ($registryModel && $registryModel->owner_id === $ownerId) {
                $this->_ownerDirty = false;
            }
        }
        $this->_objectOwner = $owner;
    }

    public function getObjectOwnerId()
    {
        if (is_object($this->objectOwner)) {
            return $this->objectOwner->primaryKey;
        }
        return $this->objectOwner;
    }

    public function getObjectOwner($pull = false)
    {
        if (is_null($this->_objectOwner) && $pull && !$this->owner->isNewRecord) {
            $registry = $this->registryModel;
            if ($registry) {
                $this->_objectOwner = $registry->owner_id;
            }
        }
        if (!is_null($this->_objectOwner) && !is_object($this->_objectOwner)) {
            $registryClass = $this->registryClass;
            $this->_objectOwner = $registryClass::getObject($this->_objectOwner, false);
        }
        return $this->_objectOwner;
    }

    public function getRegistryModel()
    {
        if (!is_null($this->_model)) {
            return $this->_model;
        }
        if (empty($this->owner->primaryKey)) {
            return false;
        }
        $registryClass = $this->registryClass;
        $registry = $registryClass::find()->disableAccessCheck()->pk($this->owner->primaryKey)->one();
        if (!empty($registry)) {
            return $registry;
        }
        return false;
    }

    public function getTable()
    {
        if (is_null(self::$_table)) {
            $_registryModel = $this->registryClass;
            self::$_table = $_registryModel::tableName();
        }
        return self::$_table;
    }

    public function beforeInsert($event)
    {
        if ($this->owner->isNewRecord && $this->owner->primaryKey == NULL) {
            $_registryModel = $this->registryClass;
            $fields = ['id' => $this->uuid(), 'object_model' => $this->owner->modelAlias, 'created' =>  new Expression('NOW()')];
            if (!empty($this->_objectOwner)) {
                $fields['owner_id'] = $this->objectOwnerId;
                $this->_ownerDirty = false;
            }
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
     * @param unknown $model (optional)
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
    public function afterSave($event)
    {
        if ($this->_ownerDirty && ($model = $this->owner->registryModel) && $model) {
            $model->owner_id = $this->objectOwnerId;
            $model->save();
        }
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
        if (!empty($this->owner->registryModel)) {
           $this->owner->registryModel->delete();
        }
        return true;
    }
}
