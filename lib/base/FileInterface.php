<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

interface FileInterface
{
    /**
     * Saves the file.
     * Note that this method uses php's move_uploaded_file() method. If the target file `$file`
     * already exists, it will be overwritten.
     *
     * @param string  $file           the file path used to save the uploaded file
     * @param boolean $deleteTempFile whether to delete the temporary file after saving.
     *                                If true, you will not be able to save the uploaded file again in the current request.
     *
     * @return boolean true whether the file is saved successfully
     *
     *                                @see error
     */
    public function saveAs($file, $deleteTempFile = true);
}
