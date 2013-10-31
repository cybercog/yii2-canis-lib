<?php
namespace infinite\console\controllers;
use Yii;
use infinite\base\exceptions\Exception;

use \infinite\console\components\CssSprite;

class SpriteController extends \yii\console\Controller
{
	use \infinite\console\ConsoleTrait;

	public function actionIndex()
	{
		$j = new CssSprite;
		$j->outputColors = array(
			'origin' => 'black',
			'60%' => 'gray',
			'ffffff' => 'white',
			'5dddfd' => 'blue',
			'57cfed' => 'darker-blue',
			'ff4830' => 'red',
			'fc1d00' => 'darker-red',
		);
		$j->outputColorsHover = array(
			'black' => 'gray',
			'white' => 'gray',
			'gray' => 'black',
			'blue' => 'white',
			'red' => 'darker-red',
			'darker-red' => 'red',
			'darker-blue' => 'blue',
		);
		$j->outputSizes = array(32, 24, 20, 16, 12);
		$j->sourceSize = 32;
		$j->sourceDirectory = dirname(dirname(dirname(__FILE__)))  . DIRECTORY_SEPARATOR .'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR .'icons' . DIRECTORY_SEPARATOR . 'original_icons';
		$j->destinationImageDir = dirname(dirname(dirname(__FILE__)))  . DIRECTORY_SEPARATOR .'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR .'icons' . DIRECTORY_SEPARATOR . 'sheets';
		$j->destinationCss = dirname(dirname(dirname(__FILE__)))  . DIRECTORY_SEPARATOR .'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR .'icons' .  DIRECTORY_SEPARATOR .'icons.css';
		$j->destinationImageRelPath = 'sheets';
		$j->destinationImageName = 'ic-icons-{size}-{color}.png';
		$j->nameClean = '/(\_[0-9x]+)/';
		$j->cssPrefix = '.ic-';

		$j->process();
	}
}
?>