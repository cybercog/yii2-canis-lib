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
     */
    protected $_object;
    /**
     */
    protected $_id;
    /**
     */
    protected $_terms;
    /**
     */
    protected $_descriptor;
    /**
     */
    protected $_subdescriptor;
    /**
     */
    protected $_score;

    /**
     * Set id.
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    /**
     * Get id.
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
     */
    public function setObject($value)
    {
        $this->_object = $value;
    }

    /**
     * Get object.
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set descriptor.
     */
    public function setDescriptor($value)
    {
        $this->_descriptor = $value;
    }

    /**
     * Get descriptor.
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
     */
    public function setTerms($value)
    {
        $this->_terms = (array) $value;
    }

    /**
     * Get terms.
     */
    public function getTerms()
    {
        if (is_null($this->_terms) && isset($this->object)) {
            $this->_terms = [];
        }

        return $this->_terms;
    }

    /**
     *
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
     */
    public function setSubdescriptor($value)
    {
        $this->_subdescriptor = (array) $value;
    }

    /**
     *
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
     *
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
     */
    public function setScore($value)
    {
        $this->_score = (float) $value;
    }

    /**
     *
     */
    public function mergeScore($value)
    {
        $this->_score = $this->score + (float) $value;
    }

    /**
     * Get score.
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
        return sprintf('%010f', $this->score/100) . '-' . $this->object->primaryKey;
    }

    /**
     *
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
