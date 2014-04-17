<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Item [@doctodo write class description for Item]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Item extends \infinite\base\Object
{
    public $type;
    public $id;
    public $descriptor;
    public $subdescriptor;
    public $isSelectable = false;
    public $hasChildren = false;

    public function package()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'descriptor' => $this->descriptor,
            'subdescriptor' => $this->subdescriptor,
            'hasChildren' => $this->hasChildren,
            'isSelectable' => $this->isSelectable
        ];
    }
}
