<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\components;

/**
 * CssSpriteImage [@doctodo write class description for CssSpriteImage]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class CssSpriteImage extends \infinite\base\Object
{
    public $path;
    public $width;
    public $height;
    public $image;
    public $name;
    public $outputs = [];

    /**
    * @inheritdoc
    **/
    public function __construct($path, $name)
    {
        $this->path = $path;
        $this->name = $name;
        $this->openImage($path);
    }

    public function getSprite($size)
    {
        if (empty($this->image)) { return false; }

        $masterWidth = (($this->width / $size) > ($this->height / $size)) ? true : false;
        if ($masterWidth) {
            // Recalculate the height based on the width
            $newHeight = round($this->height * $size / $this->width);
            $newWidth = $size;
        } else {
            // Recalculate the width based on the height
            $newWidth = round($this->width * $size / $this->height);
            $newHeight = $size;
        }

        // Create the temporary image to copy to
        $img = $this->imagecreatetransparent($size, $size);

        //echo "{$newWidth} x {$newHeight}\n";exit;
        if (!isset($this->outputs[$size])) { $this->outputs[$size] = []; }
        $this->outputs[$size]['height'] = $newHeight;
        $this->outputs[$size]['width'] = $newWidth;
        // Execute the resize
        if ($status = imagecopyresampled($img, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height)) {
            return $img;
        }

        return false;
    }

    protected function imagecreatetransparent($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        $background = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $background);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        return $img;
    }

    private function openImage($file)
    {
        $imageInfo = @getimagesize($file);
        if (!isset($imageInfo[2])) { return false; }
        $imageType = $imageInfo[2];
        if ($imageType == IMAGETYPE_JPEG) {
            $image = @imagecreatefromjpeg($file);
        } elseif ($imageType == IMAGETYPE_GIF) {
            $image = @imagecreatefromgif($file);
        } elseif ($imageType == IMAGETYPE_PNG) {
            $image = @imagecreatefrompng($file);
        }
        if ($image) {
            $this->width = $imageInfo[0];
            $this->height = $imageInfo[1];
        }
        $this->image = $image;

        return !empty($this->image);
    }
}
