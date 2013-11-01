<?php
namespace infinite\helpers;

use Yii;

use \infinite\base\View;

class Html extends \yii\helpers\Html {
	// public static function onLoadJsBlock($script, $key = null) {
	// 	if (Yii::$app->request->isAjaxRequest) {
	// 		echo self::script($script);
	// 	} else {
	// 		self::registerJsBlock($script, View::POS_READY, $key);
	// 	}
	// }

	public static function registerJsBlock($script, $position = View::POS_READY, $key = null) {
		return Yii::$app->controller->view->registerJs($script, $position, $key);
	}
}
?>