<?php
/**
 * library/helpers/FileStorage.php
 *  Application component for handling temporary files
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

namespace infinite\helpers;
use infinite\base\Exception;

class FileStorage extends \infinite\base\Component
{
    protected $_supportedImageTypes;
    protected $_tempPath;
    protected $_tempFiles = [];

    /**
     * Initializes the component
     */
    public function init()
    {
        register_shutdown_function([$this, 'deleteTempFiles']);

        return parent::init();
    }

    /**
     * Delete the tracked temporary file
     *
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
     *
     * @param  boolean $keep Keep the file after request has been processed (optional)
     * @param  unknown $ext  Extension of temporary file (optional)
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
     *
     * @param  boolean $keep Keep the file after request has been processed (optional)
     * @param  unknown $ext  Extension of temporary file (optional)
     * @return string  Temporary file path
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
