<?php
/**
 * library/db/behaviors/Blame.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;
use \yii\db\Expression;
use \yii\base\ModelEvent;

class Blame extends \infinite\db\behaviors\ActiveRecord {
	public $deletedField = 'deleted';
	public $deletedByField = 'deleted_user_id';

	public $createdField = 'created';
	public $createdByField = 'created_user_id';

	public $modifiedField = 'modified';
	public $modifiedByField = 'modified_user_id';

	static $_userID;

	public function events()
	{
		return [
			\infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			\infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
		];
	}

	protected $_fields;

	public function getFields() {
		if (is_null($this->_fields)) {
			$this->_fields = [];
			$_f = ['deletedField', 'deletedByField', 'createdField', 'createdByField', 'modifiedField', 'modifiedByField'];
			$ownerClass = get_class($this->owner);
			$schema = $ownerClass::getTableSchema();
			foreach ($_f as $field) {
				if (isset($schema->columns[$this->{$field}])) {
					$this->_fields[$field] = $this->{$field};
				}
			}
		}
		return $this->_fields;
	}

	public function beforeSave($event) {
		$fields = $this->fields;
		if ($this->owner->isNewRecord) {
			if (isset($this->fields['createdField'])) {
				$this->owner->{$this->fields['createdField']} = new Expression('NOW();');
			}
			if (isset($this->fields['createdByField'])) {
				$this->owner->{$this->fields['createdByField']} = self::_getUserId();
			}
		}

		if (isset($this->fields['modifiedField'])) {
			$this->owner->{$this->fields['modifiedField']} = new Expression('NOW();');
		}
		if (isset($this->fields['modifiedByField'])) {
			$this->owner->{$this->fields['modifiedByField']} = self::_getUserId();
		}
	}


	public function archive(Event $event = null) {
		if (is_null($event)) { $event = new ModelEvent; }
		if (!$this->isArchivable()) { $event->isValid = false; return false; }

		$this->owner->{$this->fields['deletedField']} = new Expression('NOW()');
		if (isset($this->fields['deletedByField'])) {
			$this->owner->{$this->fields['deletedByField']} = self::_getUserId();
		}
		return $this->owner->save();
	}

	public function isArchivable() {
		return isset($this->fields['deletedField']);
	}

	protected static function _getUserId() {
		if (is_null(self::$_userID)) {
			self::$_userID = null;
			if (isset(Yii::$app->user) AND !empty(Yii::$app->user->id)) {
				self::$_userID = Yii::app()->user->id;
			}
		}
		return self::$_userID;
	}
}


?>
