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

class SearchTermResult extends Object implements Arrayable
{
	public $object;
	protected $_id;
	protected $_terms;
	protected $_descriptor;
	protected $_sub;
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
			if (!in_array($this->terms)) {
				$this->_terms[] = $v;
			}
		}
	}
	
	public function setSub($value)
	{
		$this->_sub = (array)$value;
	}

	public function getSub()
	{
		if (is_null($this->_sub) && isset($this->object)) {
			$this->_sub = [];
		}
		return $this->_sub;
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
			'sub' => $this->sub,
			'score' => $this->score
		];
	}
}