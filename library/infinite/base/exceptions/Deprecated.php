<?php
/**
 * library/base/exceptions/AccessException.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base\exceptions;

class DeprecatedException extends \infinite\base\exceptions\Exception
{
	public function getName()
	{
		return 'Deprecated';
	}
}
