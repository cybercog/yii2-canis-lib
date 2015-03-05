<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

/**
 * File [[@doctodo class_description:infinite\base\File]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class File extends \infinite\web\UploadedFile implements FileInterface
{
    /**
     * [[@doctodo method_description:createInstance]].
     *
     * @param integer $error [[@doctodo param_description:error]] [optional]
     *
     * @return [[@doctodo return_type:createInstance]] [[@doctodo return_description:createInstance]]
     */
    public static function createInstance($name, $tempName, $type, $size, $error = UPLOAD_ERR_OK)
    {
        if (!file_exists($tempName)) {
            $error = UPLOAD_ERR_NO_FILE;
        }

        return new static([
                'name' => $name,
                'tempName' => $tempName,
                'type' => $type,
                'size' => $size,
                'error' => $error,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function saveAs($file, $deleteTempFile = false)
    {
        if ($deleteTempFile) {
            return rename($this->tempName, $file);
        } else {
            return copy($this->tempName, $file);
        }

        return false;
    }
}
