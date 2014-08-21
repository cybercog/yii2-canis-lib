<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;
use Yii;
use infinite\helpers\FileHelper;

/**
 * File [@doctodo write class description for File]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class RawFile extends File
{
    public static function createRawInstance($rawContent, $type = null, $name = null, $error = UPLOAD_ERR_OK)
    {
        $tmp = Yii::$app->fileStorage->getTempFile();
        file_put_contents($tmp, $rawContent);
        if (is_null($type)) {
            $type = FileHelper::getMimeType($tmp, null, false);
        }
        if (is_null($name)) {
            $extension = FileHelper::extensionFromMime($type);
            $name = md5($rawContent);
            if ($extension) {
                $name .= $extension;
            }
        }
        $size = filesize($tmp);
        return static::createInstance($name, $tmp, $type, $size, $error);
    }
}
