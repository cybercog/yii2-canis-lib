<?php
namespace infinite\data;

use Yii;

class ActiveDataProvider extends \yii\data\ActiveDataProvider {
	protected $_state;

	/**
	 *
	 *
	 * @return unknown
	 */
	public function getState() {
		return $this->_state;
	}


	/**
	 *
	 *
	 * @param unknown $state
	 */
	public function setState($state) {
		$this->_state = $state;
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
			$value->state = $this->state;
		}
		return parent::setSort($value);
	}

	/**
	 * @inheritdoc
	 */
	public function setPagination($value)
	{
		if (is_array($value)) {
			$config = ['class' => Pagination::className()];
			if ($this->id !== null) {
				$config['pageVar'] = $this->id . '-page';
			}
			$value = Yii::createObject(array_merge($config, $value));
			$value->state = $this->state;
		}
		return parent::setPagination($value);
	}
}