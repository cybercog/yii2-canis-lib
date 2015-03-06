<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use infinite\helpers\FileHelper;
use Yii;

/**
 * RawFile [[@doctodo class_description:infinite\base\RawFile]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class RawFile extends File
{
    /**
     * [[@doctodo method_description:createRawInstance]].
     *
     * @param [[@doctodo param_type:rawContent]] $rawContent [[@doctodo param_description:rawContent]]
     * @param [[@doctodo param_type:type]]       $type       [[@doctodo param_description:type]] [optional]
     * @param [[@doctodo param_type:name]]       $name       [[@doctodo param_description:name]] [optional]
     * @param integer                            $error      [[@doctodo param_description:error]] [optional]
     *
     * @return [[@doctodo return_type:createRawInstance]] [[@doctodo return_description:createRawInstance]]
     */
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
