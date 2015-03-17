<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base\exceptions;

/**
 * Deprecated [[@doctodo class_description:canis\base\exceptions\Deprecated]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Deprecated extends \canis\base\exceptions\Exception
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Deprecated';
    }
}
