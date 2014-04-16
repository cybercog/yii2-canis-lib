<?php
/**
 * library/web/Color.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

namespace infinite\web;

class UploadedFile extends \yii\web\UploadedFile implements \infinite\base\FileInterface
{
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
                return $baseInternalError. ' (TMP_DIR)';
            break;
            case UPLOAD_ERR_CANT_WRITE:
                return $baseInternalError. ' (CANT_WRITE)';
            break;
            case UPLOAD_ERR_EXTENSION:
                return $baseInternalError. ' (PHP_EXTENSION)';
            break;

        }

        return null;
    }
}
