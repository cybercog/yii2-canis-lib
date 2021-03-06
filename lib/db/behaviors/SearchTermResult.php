<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors;

use canis\base\Object;

/**
 * SearchTermResult [[@doctodo class_description:canis\db\behaviors\SearchTermResult]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class SearchTermResult extends Object
{
    /**
     * @var [[@doctodo var_type:_object]] [[@doctodo var_description:_object]]
     */
    protected $_object;
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:_terms]] [[@doctodo var_description:_terms]]
     */
    protected $_terms;
    /**
     * @var [[@doctodo var_type:_descriptor]] [[@doctodo var_description:_descriptor]]
     */
    protected $_descriptor;
    /**
     * @var [[@doctodo var_type:_subdescriptor]] [[@doctodo var_description:_subdescriptor]]
     */
    protected $_subdescriptor;
    /**
     * @var [[@doctodo var_type:_score]] [[@doctodo var_description:_score]]
     */
    protected $_score;

    /**
     * Set id.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
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
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setObject($value)
    {
        $this->_object = $value;
    }

    /**
     * Get object.
     *
     * @return [[@doctodo return_type:getObject]] [[@doctodo return_description:getObject]]
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set descriptor.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setDescriptor($value)
    {
        $this->_descriptor = $value;
    }

    /**
     * Get descriptor.
     *
     * @return [[@doctodo return_type:getDescriptor]] [[@doctodo return_description:getDescriptor]]
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
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setTerms($value)
    {
        $this->_terms = (array) $value;
    }

    /**
     * Get terms.
     *
     * @return [[@doctodo return_type:getTerms]] [[@doctodo return_description:getTerms]]
     */
    public function getTerms()
    {
        if (is_null($this->_terms) && isset($this->object)) {
            $this->_terms = [];
        }

        return $this->_terms;
    }

    /**
     * [[@doctodo method_description:mergeTerms]].
     *
     * @param [[@doctodo param_type:values]] $values [[@doctodo param_description:values]]
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
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setSubdescriptor($value)
    {
        $this->_subdescriptor = (array) $value;
    }

    /**
     * [[@doctodo method_description:addSubdescriptorField]].
     *
     * @param [[@doctodo param_type:field]] $field [[@doctodo param_description:field]]
     *
     * @return [[@doctodo return_type:addSubdescriptorField]] [[@doctodo return_description:addSubdescriptorField]]
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
     * [[@doctodo method_description:addSubdescriptorValue]].
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:addSubdescriptorValue]] [[@doctodo return_description:addSubdescriptorValue]]
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
     * @return [[@doctodo return_type:getSubdescriptor]] [[@doctodo return_description:getSubdescriptor]]
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
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setScore($value)
    {
        $this->_score = (float) $value;
    }

    /**
     * [[@doctodo method_description:mergeScore]].
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function mergeScore($value)
    {
        $this->_score = $this->score + (float) $value;
    }

    /**
     * Get score.
     *
     * @return [[@doctodo return_type:getScore]] [[@doctodo return_description:getScore]]
     */
    public function getScore()
    {
        if (is_null($this->_score)) {
            return 0;
        }

        return $this->_score;
    }

    /**
     * Get score sort.
     *
     * @return [[@doctodo return_type:getScoreSort]] [[@doctodo return_description:getScoreSort]]
     */
    public function getScoreSort()
    {
        return sprintf('%010f', $this->score/100) . '-' . $this->object->primaryKey;
    }

    /**
     * [[@doctodo method_description:toArray]].
     *
     * @return [[@doctodo return_type:toArray]] [[@doctodo return_description:toArray]]
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
