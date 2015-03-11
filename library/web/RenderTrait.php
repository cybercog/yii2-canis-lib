<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

trait RenderTrait
{
    public function output()
    {
        echo $this->generate();
    }
}
