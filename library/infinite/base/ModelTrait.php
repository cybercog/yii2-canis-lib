<?php
namespace infinite\base;

use Yii;
use infinite\db\behaviors\Model as ModelBehavior;

trait ModelTrait
{
	/**
	 * @inheritdoc
	 */
	public function safeAttributes()
	{
		$safe = parent::safeAttributes();
		foreach ($this->behaviors as $behavior) {
			if ($behavior instanceof ModelBehavior) {
				$safe = array_merge($safe, $behavior->safeAttributes());
			}
		}
		return $safe;
	}
}
?>