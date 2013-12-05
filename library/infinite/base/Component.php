<?php
/**
 * library/base/Component.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base;

use infinite\base\ObjectTrait;
use infinite\base\ComponentTrait;

class Component extends \yii\base\Component
{
	use ObjectTrait;
	use ComponentTrait;

}
