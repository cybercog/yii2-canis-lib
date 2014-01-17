<?php
namespace infinite\base\collector;


abstract class Collector extends \infinite\base\Component 
{
	use CollectorTrait;

	protected $_systemId;
	
	const DEFAULT_BUCKET = '__default';
	const EVENT_AFTER_COLLECTOR_INIT = 'afterCollectorInit';
	const EVENT_BEFORE_BUCKET_ACCESS = 'beforeBucketAccess';

	/**
	 *
	 *
	 * @param unknown $value
	 */
	public function setSystemId($value) {
		$this->_systemId = $value;
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function getSystemId() {
		return $this->_systemId;
	}
}
?>