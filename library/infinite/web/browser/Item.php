<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Item [@doctodo write class description for Item].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \infinite\base\Object
{
    /**
     * @var __var_type_type__ __var_type_description__
     */
    public $type;
    /**
     * @var __var_id_type__ __var_id_description__
     */
    public $id;
    /**
     * @var __var_descriptor_type__ __var_descriptor_description__
     */
    public $descriptor;
    /**
     * @var __var_subdescriptor_type__ __var_subdescriptor_description__
     */
    public $subdescriptor;
    /**
     * @var __var_isSelectable_type__ __var_isSelectable_description__
     */
    public $isSelectable = false;
    /**
     * @var __var_hasChildren_type__ __var_hasChildren_description__
     */
    public $hasChildren = false;

    /**
     * __method_package_description__.
     *
     * @return __return_package_type__ __return_package_description__
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
