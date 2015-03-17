<?php

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require __DIR__ . '/../../vendor/yiisoft/yii2/yii/Yii.php';

require __DIR__ . '/../../library/Canis.php';
Yii::setAlias('@canisunit', __DIR__);

require_once __DIR__ . '/TestCase.php';
