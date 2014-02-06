<?php
/**
 * library/base/Object.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base;

class File extends \infinite\web\UploadedFile implements FileInterface
{
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
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($deleteTempFile) {
            return rename($this->tempName, $file);
        } elseif (is_uploaded_file($this->tempName)) {
            return copy($this->tempName, $file);
        }
        return false;
    }
}
