<?php
namespace infinite\data;

use Yii;

class ActiveDataProvider extends \yii\data\ActiveDataProvider {
	public function setPagination($value)
	{
		if (is_array($value) && !isset($config['class'])) {
			$config['class'] = Pagination::className();
		}
		return parent::setPagination($value);
	}

	/**
	 * @inheritdoc
	 */
	public function setSort($value)
	{
		if (is_array($value)) {
			$config = ['class' => Sort::className()];
			if ($this->id !== null) {
				$config['sortVar'] = $this->id . '-sort';
			}
			$value = Yii::createObject(array_merge($config, $value));
		}
		return parent::setSort($value);
	}
}