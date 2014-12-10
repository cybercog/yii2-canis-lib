<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\cs;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Thomas Bachem <mail@thomasbachem.com>
 * @author Jacob Morrison <email@ofjacob.com>
 *
 * All actual conversion from https://github.com/thomasbachem/php-short-array-syntax-converter
 */
class DocBlockGenerator extends AbstractFixer
{
    public $tokens;
    public $content;

    protected $_classes;

    public function fix(\SplFileInfo $file, $content)
    {
        // reset
        $this->_classes = $this->match('#\n(?:abstract )?class (?<name>\w+)( extends .+)?( implements .+)?\n\{(?<content>.*)\n\}(\n|$)#', $content);

        $this->tokens = token_get_all($content);
        return $content;
    }

    public function getClassName()
    {
        if (isset($this->getClasses()[0])) {
            return $this->getClasses()[0];
        }
        return false;
    }

    public function getClasses()
    {
        return $this->_classes;
    }

    protected function match($pattern, $subject)
    {
        $sets = [];
        preg_match_all($pattern . 'suU', $subject, $sets, PREG_SET_ORDER);
        foreach ($sets as &$set)
            foreach ($set as $i => $match)
                if (is_numeric($i) /*&& $i != 0*/)
                    unset($set[$i]);

        return $sets;
    }


    public function getName()
    {
        return 'doc_block_gen';
    }

    public function getDescription()
    {
        return 'Generates phpdoc blocks';
    }
}
