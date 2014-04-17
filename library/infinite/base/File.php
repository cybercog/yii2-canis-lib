<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

/**
 * File [@doctodo write class description for File]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class File extends \infinite\web\UploadedFile implements FileInterface
{
    /**
     * __method_createInstance_description__
     * @param __param_name_type__ $name __param_name_description__
     * @param __param_tempName_type__ $tempName __param_tempName_description__
     * @param __param_type_type__ $type __param_type_description__
     * @param __param_size_type__ $size __param_size_description__
     * @param integer $error __param_error_description__ [optional]
     * @return __return_createInstance_type__ __return_createInstance_description__
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
