<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

/**
 * UploadedFile [[@doctodo class_description:teal\web\UploadedFile]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UploadedFile extends \yii\web\UploadedFile implements \teal\base\FileInterface
{
    /**
     * Get error message.
     *
     * @return [[@doctodo return_type:getErrorMessage]] [[@doctodo return_description:getErrorMessage]]
     */
    public function getErrorMessage()
    {
        $baseInternalError = 'An internal error occurred that prevented this file from being uploaded';
        switch ($this->error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The file is too big.';
            break;
            case UPLOAD_ERR_PARTIAL:
                return 'The file upload process was interupted. Please try again.';
            break;
            case UPLOAD_ERR_NO_TMP_DIR:
                return $baseInternalError . ' (TMP_DIR)';
            break;
            case UPLOAD_ERR_CANT_WRITE:
                return $baseInternalError . ' (CANT_WRITE)';
            break;
            case UPLOAD_ERR_EXTENSION:
                return $baseInternalError . ' (PHP_EXTENSION)';
            break;

        }

        return;
    }
}
