<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\base\Object;

/**
 * SearchTermResult [@doctodo write class description for SearchTermResult].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class SearchTermResult extends Object
{
    /**
     * @var __var__object_type__ __var__object_description__
     */
    protected $_object;
    /**
     * @var __var__id_type__ __var__id_description__
     */
    protected $_id;
    /**
     * @var __var__terms_type__ __var__terms_description__
     */
    protected $_terms;
    /**
     * @var __var__descriptor_type__ __var__descriptor_description__
     */
    protected $_descriptor;
    /**
     * @var __var__subdescriptor_type__ __var__subdescriptor_description__
     */
    protected $_subdescriptor;
    /**
     * @var __var__score_type__ __var__score_description__
     */
    protected $_score;

    /**
     * Set id.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    /**
     * Get id.
     *
     * @return __return_getId_type__ __return_getId_description__
     */
    public function getId()
    {
        if (is_null($this->_id) && isset($this->object)) {
            $this->_id = $this->object->primaryKey;
        }

        return $this->_id;
    }

    /**
     * Set object.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setObject($value)
    {
        $this->_object = $value;
    }

    /**
     * Get object.
     *
     * @return __return_getObject_type__ __return_getObject_description__
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set descriptor.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setDescriptor($value)
    {
        $this->_descriptor = $value;
    }

    /**
     * Get descriptor.
     *
     * @return __return_getDescriptor_type__ __return_getDescriptor_description__
     */
    public function getDescriptor()
    {
        if (is_null($this->_descriptor) && isset($this->object)) {
            $this->_descriptor = $this->object->descriptor;
        }

        return $this->_descriptor;
    }

    /**
     * Set terms.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setTerms($value)
    {
        $this->_terms = (array) $value;
    }

    /**
     * Get terms.
     *
     * @return __return_getTerms_type__ __return_getTerms_description__
     */
    public function getTerms()
    {
        if (is_null($this->_terms) && isset($this->object)) {
            $this->_terms = [];
        }

        return $this->_terms;
    }

    /**
     * __method_mergeTerms_description__.
     *
     * @param __param_values_type__ $values __param_values_description__
     */
    public function mergeTerms($values)
    {
        $values = (array) $values;
        foreach ($values as $v) {
            if (!in_array($v, $this->terms)) {
                $this->_terms[] = $v;
            }
        }
    }

    /**
     * Set subdescriptor.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setSubdescriptor($value)
    {
        $this->_subdescriptor = (array) $value;
    }

    /**
     * __method_addSubdescriptorField_description__.
     *
     * @param __param_field_type__ $field __param_field_description__
     *
     * @return __return_addSubdescriptorField_type__ __return_addSubdescriptorField_description__
     */
    public function addSubdescriptorField($field)
    {
        if (is_null($this->_subdescriptor)) {
            $this->_subdescriptor = [];
        }
        if (isset($this->object)) {
            $fieldValue = $this->object->getFieldValue($field);
            if (!empty($fieldValue) && !in_array($fieldValue, $this->_subdescriptor)) {
                $this->_subdescriptor[] = $fieldValue;

                return true;
            }
        }

        return false;
    }

    /**
     * __method_addSubdescriptorValue_description__.
     *
     * @param __param_value_type__ $value __param_value_description__
     *
     * @return __return_addSubdescriptorValue_type__ __return_addSubdescriptorValue_description__
     */
    public function addSubdescriptorValue($value)
    {
        if (is_null($this->_subdescriptor)) {
            $this->_subdescriptor = [];
        }
        if (!empty($value) && !in_array($value, $this->_subdescriptor)) {
            $this->_subdescriptor[] = $value;
        }

        return false;
    }

    /**
     * Get subdescriptor.
     *
     * @return __return_getSubdescriptor_type__ __return_getSubdescriptor_description__
     */
    public function getSubdescriptor()
    {
        if (!isset($this->_subdescriptor)) {
            $this->_subdescriptor = [];
            if (isset($this->object)) {
                foreach ($this->object->subdescriptor as $subValue) {
                    if (!empty($subValue)) {
                        if (is_array($subValue) && isset($subValue['plain'])) {
                            $this->_subdescriptor[] = $subValue['plain'];
                        } elseif (is_array($subValue) && isset($subValue['rich'])) {
                            $this->_subdescriptor[] = strip_tags($subValue['rich']);
                        } elseif (is_string($subValue) || is_numeric($subValue)) {
                            $this->_subdescriptor[] = strip_tags($subValue);
                        }
                    }
                }
            }
        }

        return $this->_subdescriptor;
    }

    /**
     * Set score.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setScore($value)
    {
        $this->_score = (float) $value;
    }

    /**
     * __method_mergeScore_description__.
     *
     * @param __param_value_type__ $value __param_value_description__
     */
    public function mergeScore($value)
    {
        $this->_score = $this->score + (float) $value;
    }

    /**
     * Get score.
     *
     * @return __return_getScore_type__ __return_getScore_description__
     */
    public function getScore()
    {
        if (is_null($this->_score)) {
            return 0;
        }

        return $this->_score;
    }

    public function getScoreSort()
    {
        return sprintf('%010f', $this->score/100).'-'.$this->object->primaryKey;
    }

    /**
     * __method_toArray_description__.
     *
     * @return __return_toArray_type__ __return_toArray_description__
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'terms' => $this->terms,
            'descriptor' => $this->descriptor,
            'subdescriptor' => $this->subdescriptor,
            'score' => $this->score,
        ];
    }
}
