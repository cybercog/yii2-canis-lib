<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

/**
 * File [[@doctodo class_description:canis\base\File]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class File extends \canis\web\UploadedFile implements FileInterface
{
    /**
     * [[@doctodo method_description:createInstance]].
     *
     * @param [[@doctodo param_type:name]]     $name     [[@doctodo param_description:name]]
     * @param [[@doctodo param_type:tempName]] $tempName [[@doctodo param_description:tempName]]
     * @param [[@doctodo param_type:type]]     $type     [[@doctodo param_description:type]]
     * @param [[@doctodo param_type:size]]     $size     [[@doctodo param_description:size]]
     * @param integer                          $error    [[@doctodo param_description:error]] [optional]
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
