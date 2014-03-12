<?php
/**
 * library/db/behaviors/SearchTerm.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use yii\db\Query;
use yii\base\Arrayable;
use infinite\base\Object;

class SearchTermResult extends Object
{
	protected $_object;
	protected $_id;
	protected $_terms;
	protected $_descriptor;
	protected $_subdescriptor;
	protected $_score;

	public function setId($value)
	{
		$this->_id = $value;
	}

	public function getId()
	{
		if (is_null($this->_id) && isset($this->object)) {
			$this->_id = $this->object->primaryKey;
		}
		return $this->_id;
	}

	public function setObject($value)
	{
		if (!isset($this->_subdescriptor)) {
			$this->_subdescriptor = [];
		}

		foreach ($value->subdescriptor as $subValue) {
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
		$this->_object = $value;
	}

	public function getObject()
	{
		return $this->_object;
	}


	public function setDescriptor($value)
	{
		$this->_descriptor = $value;
	}

	public function getDescriptor()
	{
		if (is_null($this->_descriptor) && isset($this->object)) {
			$this->_descriptor = $this->object->descriptor;
		}
		return $this->_descriptor;
	}


	public function setTerms($value)
	{
		$this->_terms = (array)$value;
	}

	public function getTerms()
	{
		if (is_null($this->_terms) && isset($this->object)) {
			$this->_terms = [];
		}
		return $this->_terms;
	}

	public function mergeTerms($values)
	{
		$values = (array)$values;
		foreach ($values as $v) {
			if (!in_array($v, $this->terms)) {
				$this->_terms[] = $v;
			}
		}
	}
	
	public function setSubdescriptor($value)
	{
		$this->_subdescriptor = (array)$value;
	}

	public function addSubdescriptorField($field)
	{
		if (is_null($this->_subdescriptor)) {
			$this->_subdescriptor = [];
		}
		if (isset($this->object)) {
			$fieldValue = $this->object->getFieldValue($field);
			if (!empty($fieldValue)) {
				$this->_subdescriptor[] = $fieldValue;
				return true;
			}
		}
		return false;
	}

	public function addSubdescriptorValue($value)
	{
		if (is_null($this->_subdescriptor)) {
			$this->_subdescriptor = [];
		}
		if (!empty($value)) {
			$this->_subdescriptor[] = $value;
		}
		return false;
	}

	public function getSubdescriptor()
	{
		if (is_null($this->_subdescriptor)) {
			$this->_subdescriptor = [];
		}
		return implode("<br />", $this->_subdescriptor);
	}

	public function setScore($value)
	{
		$this->_score = (float)$value;
	}

	public function mergeScore($value)
	{
		$this->_score = $this->score + (float)$value;
	}

	public function getScore()
	{
		if (is_null($this->_score)) {
			return 0;
		}
		return $this->_score;
	}
	
	public function toArray()
	{
		return [
			'id' => $this->id,
			'terms' => $this->terms,
			'descriptor' => $this->descriptor,
			'subdescriptor' => $this->subdescriptor,
			'score' => $this->score
		];
	}
}