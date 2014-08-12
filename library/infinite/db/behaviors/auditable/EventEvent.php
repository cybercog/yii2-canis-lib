<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;
use yii\helpers\Url;

/**
 * Event [@doctodo write class description for Event]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class EventEvent extends \yii\base\Event
{
    public $model;
}
