<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use teal\base\exceptions\Exception;

/**
 * FileStorage [[@doctodo class_description:teal\base\FileStorage]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class FileStorage extends \teal\base\Component
{
    /**
     * @var [[@doctodo var_type:_supportedImageTypes]] [[@doctodo var_description:_supportedImageTypes]]
     */
    protected $_supportedImageTypes;
    /**
     * @var [[@doctodo var_type:_tempPath]] [[@doctodo var_description:_tempPath]]
     */
    protected $_tempPath;
    /**
     * @var [[@doctodo var_type:_tempFiles]] [[@doctodo var_description:_tempFiles]]
     */
    protected $_tempFiles = [];

    /**
     * Initializes the component.
     *
     * @return [[@doctodo return_type:init]] [[@doctodo return_description:init]]
     */
    public function init()
    {
        register_shutdown_function([$this, 'deleteTempFiles']);

        return parent::init();
    }

    /**
     * Delete the tracked temporary file.
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
     * Get a temporary file.
     *
     * @param boolean $keep Keep the file after request has been processed (optional)
     * @param unknown $ext  Extension of temporary file (optional)
     *
     * @return string Temporary file path
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
     * Get a temporary path.
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return string Temporary file path
     *
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
