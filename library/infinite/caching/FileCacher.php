<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\caching;

use Yii;

use yii\caching\ChainedDependency;
use yii\caching\TagDependency;
use yii\caching\DbDependency;

class FileCacher extends Cacher
{
    public static $component = 'fileCache';
}
