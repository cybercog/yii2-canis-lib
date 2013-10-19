<?php
namespace infinite;

use Yii;
$classesFile = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'classes.php';
if (is_file($classesFile)) {
	Yii::$classMap =  array_merge(Yii::$classMap, include($classesFile));
}

Yii::importNamespaces(['infinite' => [dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library']]);

class Infinite {

}
?>