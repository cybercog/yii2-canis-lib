<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use infinite\base\Exception;

/**
 * FileStorage [@doctodo write class description for FileStorage]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class FileStorage extends \infinite\base\Component
{
    /**
     * @var __var__supportedImageTypes_type__ __var__supportedImageTypes_description__
     */
    protected $_supportedImageTypes;
    /**
     * @var __var__tempPath_type__ __var__tempPath_description__
     */
    protected $_tempPath;
    /**
     * @var __var__tempFiles_type__ __var__tempFiles_description__
     */
    protected $_tempFiles = [];

    /**
     * Initializes the component
     * @return __return_init_type__ __return_init_description__
     */
    public function init()
    {
        register_shutdown_function([$this, 'deleteTempFiles']);

        return parent::init();
    }

    /**
     * Delete the tracked temporary file
     * @return boolean status of temp file deletion
     */
    public function deleteTempFiles()
    {
        foreach ($this->_tempFiles as $tmp) {
            @unlink($tmp);
        }

        return true;
    }

    /**
     * Get a temporary file
     * @param boolean $keep Keep the file after request has been processed (optional)
     * @param unknown $ext  Extension of temporary file (optional)
     * @return string  Temporary file path
     */
    public function getTempFile($keep = false, $ext = null)
    {
        $tmp = tempnam($this->tempPath, "tmp_");
        $tmpExt = '';
        if (!is_null($ext)) {
            $tmpExt .= ".{$ext}";
        }
        if (!$keep) {
            $this->_tempFiles[] = $tmp;
            if (!empty($tmpExt)) {
                $tmp = $tmp . $tmpExt;
                $this->_tempFiles[] = $tmp;
            }
        } else {
            if (!empty($tmpExt)) {
                $tmp = $tmp . $tmpExt;
            }
        }

        return $tmp;
    }

    /**
     * Get a temporary path
     * @return string  Temporary file path
     * @throws Exception __exception_Exception_description__
     */
    public function getTempPath()
    {
        if (is_null($this->_tempPath)) {
            $this->_tempPath = sys_get_temp_dir();
        }
        if (!is_dir($this->_tempPath)) {
            @mkdir($this->_tempPath, 0755, true);
            if (!is_dir($this->_tempPath)) {
                throw new Exception("Unable to create temporary path folder {$this->_tempPath}");
            }
        }

        return $this->_tempPath;
    }
}
