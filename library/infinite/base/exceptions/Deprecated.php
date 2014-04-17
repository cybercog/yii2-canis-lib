<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\exceptions;

/**
 * Deprecated [@doctodo write class description for Deprecated]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Deprecated extends \infinite\base\exceptions\Exception
{
    /**
    * @inheritdoc
    **/
    public function getName()
    {
        return 'Deprecated';
    }
}
