<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Item [[@doctodo class_description:infinite\web\browser\Item]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:type]] [[@doctodo var_description:type]]
     */
    public $type;
    /**
     * @var [[@doctodo var_type:id]] [[@doctodo var_description:id]]
     */
    public $id;
    /**
     * @var [[@doctodo var_type:descriptor]] [[@doctodo var_description:descriptor]]
     */
    public $descriptor;
    /**
     * @var [[@doctodo var_type:subdescriptor]] [[@doctodo var_description:subdescriptor]]
     */
    public $subdescriptor;
    /**
     * @var [[@doctodo var_type:isSelectable]] [[@doctodo var_description:isSelectable]]
     */
    public $isSelectable = false;
    /**
     * @var [[@doctodo var_type:hasChildren]] [[@doctodo var_description:hasChildren]]
     */
    public $hasChildren = false;

    /**
     * [[@doctodo method_description:package]].
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
    public function package()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'descriptor' => $this->descriptor,
            'subdescriptor' => $this->subdescriptor,
            'hasChildren' => $this->hasChildren,
            'isSelectable' => $this->isSelectable,
        ];
    }
}
