<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base\exceptions;

/**
 * Deprecated [[@doctodo class_description:teal\base\exceptions\Deprecated]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Deprecated extends \teal\base\exceptions\Exception
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Deprecated';
    }
}
