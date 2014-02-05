<?php
namespace infinite\helpers;

use Yii;
use yii\base\InvalidParamException;

use infinite\web\View;
class Html extends \yii\helpers\Html {
	// public static function onLoadJsBlock($script, $key = null) {
	// 	if (Yii::$app->request->isAjax) {
	// 		echo self::script($script);
	// 	} else {
	// 		self::registerJsBlock($script, View::POS_READY, $key);
	// 	}
	// }

	public static function registerJsBlock($script, $position = View::POS_READY, $key = null) {
		return Yii::$app->controller->view->registerJs($script, $position, $key);
	}

	public static function addSubAttribute($attribute, $subattribute) {
		if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
			throw new InvalidParamException('Attribute name must contain word characters only.');
		}
		$prefix = $matches[1];
		$attribute = $matches[2];
		return $prefix . "[{$attribute}]" . $subattribute;
	}

	public static function addPreAttribute($attribute, $preattribute) {
		if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
			throw new InvalidParamException('Attribute name must contain word characters only.');
		}
		$prefix = $matches[1];
		$attribute = $matches[2];
		return $prefix . "[{$preattribute}]" . $attribute;
	}

	public static function changeAttribute($attribute, $newAttribute) {
		if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
			throw new InvalidParamException('Attribute name must contain word characters only '. $attribute.'.');
		}
		$prefix = $matches[1];
		return $prefix . $newAttribute;
	}

}
?>