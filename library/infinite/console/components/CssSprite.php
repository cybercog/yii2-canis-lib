<?php

namespace infinite\console\components;

use infinite\base\exceptions\Exception;

class CssSprite extends \infinite\base\Object
{
    public $sourceDirectory;
    public $imgOutputDirectory;
    public $outputSizes;
    public $outputColors = [];
    public $outputColorsHover = [];
    public $sourceSize;
    public $nameClean = false;
    public $destinationImageDir;
    public $destinationImageRelPath;
    public $destinationImageName;
    public $destinationCss;
    public $footer = '';
    public $cssPrefix = '.';
    public $cssIconTemplate = "{prefix}icon-{size}{prefix}icon-{name} { background-position: {x} {y}; }";
    public $cssDefaultTemplate = "{prefix}icon-{size} {  display: block; background-image: url('{path}'); background-repeat: no-repeat; width: {size}px; height: {size}px; }";
    public $cssColorTemplate = "{prefix}icon-{size}{prefix}icon-{color} {  display: block; background-image: url('{path}'); background-repeat: no-repeat; width: {size}px; height: {size}px; }";
    public $cssColorHoverTemplate = "a{prefix}icon-{size}{prefix}icon-{color}:hover, {prefix}icon-{size}{prefix}icon-{color}.ui-state-active {  display: block; background-image: url('{path}'); background-repeat: no-repeat; width: {size}px; height: {size}px; }\na:hover span{prefix}icon-{size}{prefix}icon-{color} {  display: block; background-image: url('{path}'); background-repeat: no-repeat; width: {size}px; height: {size}px; }";
    public $cssColorHoverTemplateFooter = "a{prefix}icon-{size}{prefix}icon-hover-{color}:hover {  display: block; background-image: url('{path}'); background-repeat: no-repeat; width: {size}px; height: {size}px; }";

/*public $cssIconTemplate = "{prefix}icon-{size}{prefix}icon-{name} { background-position: {x} {y}; width: {width}px; height: {height}px; }";
    public $cssDefaultTemplate = "{prefix}icon-{size} {  display: block; background-image: url('{path}'); background-repeat: no-repeat; }";
    public $cssColorTemplate = "{prefix}icon-{size}{prefix}icon-{color} {  display: block; background-image: url('{path}'); background-repeat: no-repeat; }";
    public $cssColorHoverTemplate = "{prefix}icon-{size}{prefix}icon-{color}:hover {  display: block; background-image: url('{path}'); background-repeat: no-repeat; }";

*/
    public $defaultColor = 'black';

    public $padding = 5; // pixels of padding
    public $maxHeight = 2000;

    protected $_images = [];
    protected $_imagesRaw = [];
    protected $_css = [];
    protected $_css_footer = [];

    public function process()
    {
        $this->_openDirectory();
        foreach ($this->outputSizes as $size) {
            $this->_css[] = '/* Icon Size: '.$size.'px */';
            $this->_cssDefaultHeader($size);
            foreach ($this->outputColors as $color => $colorName) {
                $this->_buildSprite($size, $colorName, $color);
                $this->_cssColorHeader($size, $colorName);
                if (isset($this->outputColorsHover[$colorName])) {
                    $this->_cssColorHoverHeader($size, $colorName, $this->outputColorsHover[$colorName]);
                    $this->_cssColorHoverFooter($size, $colorName, $this->outputColorsHover[$colorName]);
                }
            }
            $this->_css[] = '';
            $this->_css[] = '';
            foreach ($this->_images as $k => $image) {
                $this->_css[] = $this->_cssSprite($size, $image, $image->outputs[$size]);
            }
            $this->_css[] = '';
            $this->_css[] = '';
            $this->_css[] = '';
        }
        $this->_saveCss();
    }

    private function _cssDefaultHeader($size)
    {
        $template = $this->cssDefaultTemplate;
        $option['prefix'] = $this->cssPrefix;
        $option['size'] = $size;
        $option['path'] = $this->_getSpriteRelPath($size, $this->defaultColor);
        foreach ($option as $key => $value) {
            $template = preg_replace('/\{'.$key.'\}/', $value, $template);
        }
        $this->_css[] = $template;
    }

    private function _getSpriteRelPath($size, $color = null)
    {
        $name = $this->destinationImageName;
        if ($color === null) {
            $color = $this->defaultColor;
        }
        $name = preg_replace('/\{color\}/', $color, $name);
        $name = preg_replace('/\{size\}/', $size, $name);

        return $this->destinationImageRelPath .'/'. $name;
    }

    private function _cssColorHeader($size, $color)
    {
        $template = $this->cssColorTemplate;
        $option['prefix'] = $this->cssPrefix;
        $option['size'] = $size;
        $option['color'] = $color;
        $option['path'] = $this->_getSpriteRelPath($size, $color);
        foreach ($option as $key => $value) {
            $template = preg_replace('/\{'.$key.'\}/', $value, $template);
        }
        $this->_css[] = $template;
    }
    private function _cssColorHoverHeader($size, $color, $hoverColor)
    {
        $template = $this->cssColorHoverTemplate;
        $option['prefix'] = $this->cssPrefix;
        $option['size'] = $size;
        $option['color'] = $color;
        $option['path'] = $this->_getSpriteRelPath($size, $hoverColor);
        foreach ($option as $key => $value) {
            $template = preg_replace('/\{'.$key.'\}/', $value, $template);
        }
        $this->_css[] = $template;
    }
    private function _cssColorHoverFooter($size, $color, $hoverColor)
    {
        $template = $this->cssColorHoverTemplateFooter;
        $option['prefix'] = $this->cssPrefix;
        $option['size'] = $size;
        $option['color'] = $color;
        $option['path'] = $this->_getSpriteRelPath($size, $color);
        foreach ($option as $key => $value) {
            $template = preg_replace('/\{'.$key.'\}/', $value, $template);
        }
        $this->_css_footer[] = $template;
    }

    private function _saveCss()
    {
        if (empty($this->destinationCss)) {
            throw new Exception("Destination CSS is not set!");;
        }
        @unlink($this->destinationCss);
        @file_put_contents($this->destinationCss, implode($this->_css, "\n") ."\n". implode($this->_css_footer, "\n"));

        return file_exists($this->destinationCss);
    }

    private function _openDirectory()
    {
        if (empty($this->sourceDirectory)) {
            throw new Exception('Source Directory is not set');
        }
        $o = opendir($this->sourceDirectory);
        if (!$o) {
            throw new Exception("Could not open source directory {$this->sourceDirectory}");
        }
        while (($file = readdir($o)) !== false) {
            $path = $this->sourceDirectory . DIRECTORY_SEPARATOR . $file;
            if (substr($file, 0, 1) === '.' OR is_dir($path)) { continue; }
            $name = $this->_getName($path);
            $this->_images[$name] = new CssSpriteImage($path, $name);
        }

        return true;
    }

    protected function _buildSprite($size, $colorName = null, $color = null)
    {
        $img = imagecreatetruecolor($this->getWidth($size), $this->getHeight($size));
        $background = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $background);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $posX = 0;
        $posY = 0;
        foreach ($this->_images as $image) {
            if (($posY + $size) > $this->maxHeight) {
                $posY = 0;
                $posX += ($size + $this->padding);
            }
            $sprite = $image->getSprite($size, $color);
            $xOffset = 0;
            $yOffset = 0;
            if ($image->outputs[$size]['width'] < $size) {
                $xOffset = round(($size - $image->outputs[$size]['width']) / 2);
            }
            if ($image->outputs[$size]['height'] < $size) {
                $yOffset = round(($size - $image->outputs[$size]['height']) / 2);
            }
            imagecopy($img, $sprite, $posX+$xOffset, $posY+$yOffset, 0, 0, $size, $size);

            if (!isset($image->outputs[$size])) { $image->outputs[$size] = []; }
            $image->outputs[$size]['x'] = (empty($posX) ? 0 : '-'.$posX .'px');
            $image->outputs[$size]['y'] = (empty($posY) ? 0 : '-'.$posY .'px');
            $posY += ($size + $this->padding);
        }
        $dest = $this->getDestinationImage($size, $colorName);
        if (!empty($color) AND $color !== 'origin') {
            $img = $this->colorize($img, $color);
        }
        imagepng($img, $dest, 1);
    }

    private function _cssSprite($size, $image, $option)
    {
        $template = $this->cssIconTemplate;
        $option['prefix'] = $this->cssPrefix;
        $option['size'] = $size;
        $option['name'] = $image->name;
        foreach ($option as $key => $value) {
            $template = preg_replace('/\{'.$key.'\}/', $value, $template);
        }

        return $template;
    }

    private function colorize($image, $color, $contrast = 0)
    {
        if (!$image) { return false; }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        if (substr($color, -1) === '%') {
            # Convert hex colour into RGB values
            $a = round(((100 - substr($color, 0, -1)) / 100) * 255);
            imagefilter($image, IMG_FILTER_BRIGHTNESS, $a);
        } else {
            # Convert hex colour into RGB values
            $r = hexdec('0x' . $color{0} . $color{1});
            $g = hexdec('0x' . $color{2} . $color{3});
            $b = hexdec('0x' . $color{4} . $color{5});

            imagefilter($image, IMG_FILTER_COLORIZE, $r, $g, $b);
        }
        imagefilter($image, IMG_FILTER_CONTRAST, $contrast);

        return $image;
    }

    protected function getDestinationImage($size, $color = null)
    {
        $path = $this->destinationImageDir . DIRECTORY_SEPARATOR . $this->destinationImageName;
        if ($color === null) {
            $color = $this->defaultColor;
        }
        $path = preg_replace('/\{color\}/', $color, $path);
        $path = preg_replace('/\{size\}/', $size, $path);

        return $path;
    }

    protected function getWidth($size)
    {
        $count = count($this->_images);

        $height = ($size + $this->padding) * $count;
        if ($height > $this->maxHeight) {
            return (int) (ceil($height / $this->maxHeight) * ($size + $this->padding));
        } else {
            return (int) $size;
        }
    }

    protected function getHeight($size)
    {
        $count = count($this->_images);
        $height = ($size + $this->padding) * $count;
        if ($height > $this->maxHeight) {
            return (int) $this->maxHeight;
        } else {
            return (int) $height;
        }
    }

    protected function _getName($filename)
    {
        $p = pathinfo($filename);
        if (!$this->nameClean) {
            return $p['filename'];
        } else {
            return preg_replace($this->nameClean, '', $p['filename']);
        }
    }
}
