<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\cs;

use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use Symfony\CS\AbstractFixer;
use Symfony\CS\FixerInterface;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Thomas Bachem <mail@thomasbachem.com>
 * @author Jacob Morrison <email@ofjacob.com>
 *
 * All actual conversion from https://github.com/thomasbachem/php-short-array-syntax-converter
 */
class DocBlockGenerator extends AbstractFixer
{
    public $params = [];
    
    public function getPriority()
    {
        return 100000;
    }


    public function getLevel()
    {
        return FixerInterface::PSR0_LEVEL;
    }

    /**
     *
     */
    public function fix(\SplFileInfo $file, $content)
    {
        // file_put_contents($file->getRealPath(), $content);
        //echo $file->getRealPath() .'...'. PHP_EOL;
        $classPropertyDocs = static::generateClassPropertyDocs($file, $content);
        if ($classPropertyDocs === false) {
            return $content;
        }
        list($className, $propertyDoc, $coveredProperties) = $classPropertyDocs;

        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file->getRealPath())) {
            static::stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n");
            return false;
        }
        $oldDoc = $ref->getDocComment();
        $oldDocSize = count(explode("\n", $oldDoc));
        $startLine = $ref->getStartLine()-1;
        if (empty($oldDoc)) {
            $oldDocSize = 0;
            $oldDoc = "/**\n*/";
        } else {
            $startLine -= $oldDocSize;
        }

        if (true || !$ref->isSubclassOf('yii\base\Object') && $className != 'yii\base\Object') {
            $newDoc = $oldDoc;
        } else {
            $newDoc = static::cleanDocComment(static::updateDocComment($oldDoc, $propertyDoc, $coveredProperties, $ref));
        }
        $seenSince = false;
        $seenAuthor = false;

        // TODO move these checks to different action
        $lines = explode("\n", trim($newDoc));
        $originalCount = count($lines);
        $oldTest = ' * ' . $ref->getShortName();
        if (strpos($lines[1], $oldTest) !== 0) {
            if ($originalCount > 2 && substr(trim($lines[1]), 0, 3) !== '* @') {
                $lines[1] = " * " . $ref->getShortName() . " " . static::guessClassDescription($ref);
            } else {
                array_splice($lines, 1, 0, [" * " . $ref->getShortName() . " " . static::guessClassDescription($ref), ' *']);
            }
            // if ($originalCount > 2) {
            //     var_dump([$lines, $originalCount, $oldTest]);exit;
            // }
        }

        if (trim($lines[1]) == '*' || substr(trim($lines[1]), 0, 3) == '* @') {
            static::stderr("[WARN] Class $className has no short description.\n");
        }
        foreach ($lines as $line) {
            if (substr(trim($line), 0, 9) == '* @since ') {
                $seenSince = true;
            } elseif (substr(trim($line), 0, 10) == '* @author ') {
                $seenAuthor = true;
            }
        }

        if (!$seenSince) {
            //static::stderr("[ERR] No @since found in class doc in file: $file\n", Console::FG_RED);
        }
        if (!$seenAuthor) {
            $insertLines = [];
            if (strpos($lines[count($lines)-2], " * @since") === false
                && $lines[count($lines)-2] !== ' *') {
                $insertLines[] = ' *';
            }
            $insertLines[] = " * @author ". static::getParam('author', 'Jacob Morrison <email@ofjacob.com>');
            array_splice($lines, count($lines)-1, 0, $insertLines);
            // static::stderr("[ERR] No @author found in class doc in file: $file\n", Console::FG_RED);
        }
        $newDoc = implode("\n", $lines) . "\n";
        $updates = [];
        $fileContent = explode("\n", $content);
        if (trim($oldDoc) != trim($newDoc)) {
            $start = $startLine;
            $updates[] = [
                'inject' => $lines,
                'length' => $oldDocSize,
                'start' => $start,
            ];
        }
        $updates = array_merge($updates, static::updateMethodDocs($fileContent, $className, $file));
        $updates = array_merge($updates, static::updatePropertyDocs($fileContent, $className, $file));
        ArrayHelper::multisort($updates, 'start', SORT_ASC, SORT_NUMERIC);
        $offset = 0;
        // var_dump($updates);
        if (!empty($updates)) {
            foreach ($updates as $update) {
                array_splice($fileContent, $update['start'] + $offset, $update['length'], $update['inject']);
                $offset = $offset + (count($update['inject']) - $update['length']);
            }
        }
        $fileContent = implode("\n", $fileContent);

        return $fileContent;
    }

    public function getParam($param, $default = false)
    {
        if (static::params[$param] !== null) {
            return static::params[$param];
        }
        return $default;
    }

    public function getName()
    {
        return 'doc_block_gen';
    }

    public function getDescription()
    {
        return 'Generates phpdoc blocks';
    }


    protected static function match($pattern, $subject)
    {
        $sets = [];
        preg_match_all($pattern . 'suU', $subject, $sets, PREG_SET_ORDER);
        foreach ($sets as &$set) {
            foreach ($set as $i => $match) {
                if (is_numeric($i) /*&& $i != 0*/) {
                    unset($set[$i]);
                }
            }
        }

        return $sets;
    }

    /**
     *
     */
    protected function generateClassPropertyDocs($fileName)
    {
        $phpdoc = "";
        $file = str_replace("\r", "", str_replace("\t", " ", file_get_contents($fileName, true)));
        $ns = static::match('#\nnamespace (?<name>[\w\\\\]+);\n#', $file);
        $namespace = reset($ns);
        $namespace = $namespace['name'];
        $classes = static::match('#\n(?:abstract )?class (?<name>\w+)( extends .+)?( implements .+)?\n\{(?<content>.*)\n\}(\n|$)#', $file);

        if (count($classes) > 1) {
            static::stderr("[ERR] There should be only one class in a file: $fileName\n");

            return false;
        }
        if (count($classes) < 1) {
            $interfaces = static::match('#\ninterface (?<name>\w+)( extends .+)?\n\{(?<content>.+)\n\}(\n|$)#', $file);
            if (count($interfaces) == 1) {
                return false;
            } elseif (count($interfaces) > 1) {
                static::stderr("[ERR] There should be only one interface in a file: $fileName\n");
            } else {
                $traits = static::match('#\ntrait (?<name>\w+)\n\{(?<content>.+)\n\}(\n|$)#', $file);
                if (count($traits) == 1) {
                    return false;
                } elseif (count($traits) > 1) {
                    static::stderr("[ERR] There should be only one class/trait/interface in a file: $fileName\n");
                } else {
                    static::stderr("[ERR] No class in file: $fileName\n");
                }
            }

            return false;
        }

        $className = null;
        foreach ($classes as &$class) {
            $className = $namespace . '\\' . $class['name'];

            $gets = static::match(
                '#\* @return (?<type>[\w\\|\\\\\\[\\]]+)(?: (?<comment>(?:(?!\*/|\* @).)+?)(?:(?!\*/).)+|[\s\n]*)\*/' .
                '[\s\n]{2,}public function (?<kind>get)(?<name>\w+)\((?:,? ?\$\w+ ?= ?[^,]+)*\)#',
                $class['content']);
            $sets = static::match(
                '#\* @param (?<type>[\w\\|\\\\\\[\\]]+) \$\w+(?: (?<comment>(?:(?!\*/|\* @).)+?)(?:(?!\*/).)+|[\s\n]*)\*/' .
                '[\s\n]{2,}public function (?<kind>set)(?<name>\w+)\(\$\w+(?:, ?\$\w+ ?= ?[^,]+)*\)#',
                $class['content']);
            // check for @property annotations in getter and setter
            $properties = static::match(
                '#\* @(?<kind>property) (?<type>[\w\\|\\\\\\[\\]]+)(?: (?<comment>(?:(?!\*/|\* @).)+?)(?:(?!\*/).)+|[\s\n]*)\*/' .
                '[\s\n]{2,}public function [g|s]et(?<name>\w+)\(((?:,? ?\$\w+ ?= ?[^,]+)*|\$\w+(?:, ?\$\w+ ?= ?[^,]+)*)\)#',
                $class['content']);
            $acrs = array_merge($properties, $gets, $sets);

            $props = [];
            foreach ($acrs as &$acr) {
                if ($acr['type'] === '\yii\db\ActiveRelation') {
                    continue;
                }
                $acr['name'] = lcfirst($acr['name']);
                $acr['comment'] = trim(preg_replace('#(^|\n)\s+\*\s?#', '$1 * ', $acr['comment']));
                $props[$acr['name']][$acr['kind']] = [
                    'type' => $acr['type'],
                    'comment' => static::fixSentence($acr['comment']),
                ];
            }

            ksort($props);
            $coveredProperties = [];
            if (count($props) > 0) {
                $phpdoc .= " *\n";
                foreach ($props as $propName => &$prop) {
                    $docline = ' * @';
                    $docline .= 'property'; // Do not use property-read and property-write as few IDEs support complex syntax.
                    $note = '';
                    if (isset($prop['get']) && isset($prop['set'])) {
                        if ($prop['get']['type'] != $prop['set']['type']) {
                            $note = ' Note that the type of this property differs in getter and setter.'
                                  . ' See [[get' . ucfirst($propName) . '()]] and [[set' . ucfirst($propName) . '()]] for details.';
                        }
                    } elseif (isset($prop['get'])) {
                        // check if parent class has setter defined
                        $c = $className;
                        $parentSetter = false;
                        while ($parent = get_parent_class($c)) {
                            if (method_exists($parent, 'set' . ucfirst($propName))) {
                                $parentSetter = true;
                                break;
                            }
                            $c = $parent;
                        }
                        if (!$parentSetter) {
                            $note = ' This property is read-only.';
//                          $docline .= '-read';
                        }
                    } elseif (isset($prop['set'])) {
                        // check if parent class has getter defined
                        $c = $className;
                        $parentGetter = false;
                        while ($parent = get_parent_class($c)) {
                            if (method_exists($parent, 'set' . ucfirst($propName))) {
                                $parentGetter = true;
                                break;
                            }
                            $c = $parent;
                        }
                        if (!$parentGetter) {
                            $note = ' This property is write-only.';
//                          $docline .= '-write';
                        }
                    } else {
                        continue;
                    }
                    $coveredProperties[] = $propName;
                    $docline .= ' ' . static::getPropParam($prop, 'type') . " $$propName ";
                    $comment = explode("\n", static::getPropParam($prop, 'comment') . $note);
                    foreach ($comment as &$cline) {
                        $cline = ltrim($cline, '* ');
                    }
                    $docline = wordwrap($docline . implode(' ', $comment), 110, "\n * ") . "\n";

                    $phpdoc .= $docline;
                }
                $phpdoc .= " *\n";
            }
        }

        return [$className, $phpdoc, $coveredProperties];
    }

    /**
     *
     */
    protected function fixFileDoc(&$lines, $file)
    {
        // find namespace
        $namespace = false;
        $namespaceLine = '';
        $contentAfterNamespace = false;
        foreach ($lines as $i => $line) {
            if (substr(trim($line), 0, 9) === 'namespace') {
                $namespace = $i;
                $namespaceLine = trim($line);
            } elseif ($namespace !== false && trim($line) !== '') {
                $contentAfterNamespace = $i;
                break;
            }
        }

        if ($namespace !== false && $contentAfterNamespace !== false) {
            while ($contentAfterNamespace > 0) {
                array_shift($lines);
                $contentAfterNamespace--;
            }
            $baseDoc = [];
            $baseDoc[] = '<?php';
            $baseDoc[] = '/**';
            if (static::getParam('link', false)) {
                $baseDoc[] = ' * @link ' . static::getParam('link');
            }
            if (static::getParam('copyright', false)) {
                $baseDoc[] = ' * @copyright ' . static::getParam('copyright');
            }
            if (static::getParam('license', false)) {
                $baseDoc[] = ' * @license ' . static::getParam('license');
            }
            if (static::getParam('package', false)) {
                $baseDoc[] = ' * @package ' . static::getParam('package');
            }
            if (static::getParam('since', false)) {
                $baseDoc[] = ' * @since ' . static::getParam('since');
            }
            $baseDoc[] = '*/';
            $baseDoc[] = '';
            $baseDoc[] = $namespaceLine;
            $baseDoc[] = '';
            $lines = array_merge($baseDoc, $lines);
        }
    }

    /**
     *
     */
    protected function guessClassDescription($ref)
    {
        $description = '[[@doctodo class_description:' . $ref->getName() . ']]';
        if ($ref->isSubclassOf('yii\db\ActiveRecord')) {
            $className = $ref->getName();
            $tableName = $className::tableName();
            $description = "is the model class for table \"{$tableName}\".";
        }

        return $description;
    }



    /**
     *
     */
    public function guessMethodDescription($method)
    {
        if ($method->getName() === '__construct') {
            return 'Constructor.';
        } elseif ($method->getName() === 'init') {
            return 'Initializes.';
        } elseif ($method->getName() === '__sleep') {
            return 'Prepares object for serialization.';
        } elseif ($method->getName() === '__toString') {
            return 'Converts object to string.';
        } elseif (substr($method->getName(), 0, 3) === 'get') {
            return 'Get ' . strtolower(Inflector::titleize(substr($method->getName(), 3)));
        } elseif (substr($method->getName(), 0, 3) === 'set') {
            return 'Set ' . strtolower(Inflector::titleize(substr($method->getName(), 3)));
        }

        return '[[@doctodo method_description:' . $method->getName() . ']]';
    }

    /**
     *
     */
    public function generatePropertyDocs($class, $property, $lines)
    {
        $phpdoc = new \phpDocumentor\Reflection\DocBlock($property);
        $currentTags = $phpdoc->getTags();
        $currentVars = [];
        foreach ($phpdoc->getTagsByName('var') as $param) {
            $currentVars[$param->getVariableName()] = $param;
        }
        if (empty($currentVars)) {
            $type = '[[@doctodo var_type:' . $property->getName() . ']]';
            $dummyObject = false;
            // try {
            //     $dummyClass = $class->getName();
            //     if (!$class->isAbstract()) {
            //         $dummyObject = new $dummyClass;
            //     }
            // } catch (\Exception $e) {
            //     $dummyObject = false;
            // }
            if ($dummyObject && $property->isPublic()) {
                $value = $property->getValue($dummyObject);
                if (isset($value)) {
                    $type = gettype($property->getValue($dummyObject));
                }
            }
            $varObject = new \phpDocumentor\Reflection\DocBlock\Tag\ParamTag(
                'var',
                $type . ' $' . $property->getName() . ' [[@doctodo var_description:' . $property->getName() . ']]'
            );
            $currentVars[$varObject->getVariableName()] = $varObject;
        }
        $lines = ['/**'];
        if (!empty($currentVars)) {
            foreach ($currentVars as $var) {
                $lines[] = ' * @var ' . trim($var->getType(), '\\') . ' ' . $var->getDescription();
                break;
            }
        }
        $lines[] = ' */';

        return $lines;
    }

    /**
     *
     */
    public function generateMethodDocs($method, $lines)
    {
        if (trim($lines[1]) == '*' || substr(trim($lines[1]), 0, 3) == '* @') {
            array_splice($lines, 1, 0, [" * " . static::guessMethodDescription($method), ' *']);
        }
        $lineDescription = null;
        $longDescription = [];
        $phpdoc = new \phpDocumentor\Reflection\DocBlock($method);
        $currentTags = $phpdoc->getTags();
        $currentParams = [];
        foreach ($phpdoc->getTagsByName('param') as $param) {
            $currentParams[$param->getVariableName()] = $param;
        }
        foreach ($method->getParameters() as $param) {
            if (!isset($currentParams['$' . $param->getName()])) {
                $type = '[[@doctodo param_type:' . $param->getName() . ']]';
                $defaultValue = $param->isOptional() ? $param->getDefaultValue() : null;
                if ($param->getClass() !== null) {
                    $type = $param->getClass()->getName();
                } elseif (isset($defaultValue)) {
                    $type = gettype($defaultValue);
                }
                $extra = '';
                if ($param->isOptional()) {
                    $extra = ' [optional]';
                }

                $paramObject = new \phpDocumentor\Reflection\DocBlock\Tag\ParamTag(
                    'param',
                    $type . ' $' . $param->getName() . ' [[@doctodo param_description:' . $param->getName() . ']]' . $extra
                );
                $currentParams['$' . $param->getName()] = $paramObject;
            }
        }

        $currentThrows = [];
        foreach ($phpdoc->getTagsByName('throws') as $throw) {
            $currentThrows[trim($throw->getType(), '\\')] = $throw;
        }
        $currentReturn = $phpdoc->getTagsByName('return');
        foreach ($currentReturn as $return) {
            if ($return->getContent() === '\yii\db\ActiveRelation') {
                $lineDescription = ' * Get related ' . Inflector::titleize(substr($method->getName(), 3), true) . ' objects';
            }
        }

        if (substr(trim($lines[1]), 0, 2) === '* '
            && substr(trim($lines[1]), 0, 3) !== '* @'
            ) {
            $lineDescription = ' ' . trim($lines[1]);
        }
        if (!isset($lineDescription) || trim($lineDescription) === '* [[@doctodo method_description' . $method->getName() . ']]') {
            $lineDescription = ' * ' . static::guessMethodDescription($method);
        }

        foreach (array_slice($lines, 2) as $line) {
            if (substr(trim($line), 0, 2) === '* '
                && substr(trim($line), 0, 3) !== '* @'
                ) {
                $longDescription[] = $line;
            }
        }

        $lines = ['/**'];
        $lines[] = $lineDescription;
        $lines = array_merge($lines, $longDescription);
        if (!empty($longDescription)) {
            $lines[] = ' *';
        }
        $parameters = $method->getParameters();
        if (!empty($parameters)) {
            foreach ($parameters as $param) {
                if (isset($currentParams['$' . $param->getName()])) {
                    $lines[] = ' * @param ' . $currentParams['$' . $param->getName()]->getContent();
                } else {
                    var_dump($currentParams);
                    var_dump([$method->getFileName(), $method->getName(), $param->getName()]); exit;
                }
            }
        }
        $hasReturnInFunction = false;
        $methodCode = static::getMethodCode($method);
        // var_dump($methodCode);
        $tokens = token_get_all('<?php ' . $methodCode . ' ?>');
        $returnType = [];
        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_RETURN:
                        $hasReturnInFunction = true;
                    break;
                    case T_THROW:
                        if (isset($tokens[$i+4][1])) {
                            $type = $tokens[$i+4][1];
                            if (!isset($currentThrows[$type])) {
                                $currentThrows[$type] = new \phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag('throws', $type . ' [[@doctodo exception_description:' . $type . ']]');
                            }
                        }
                    break;
                }
            }
        }
        if (empty($returnType)) {
            $returnType = ['[[@doctodo return_type:' . $method->getName() . ']]'];
        }
        $hasReturn = false;
        foreach ($currentReturn as $return) {
            $hasReturn = true;
            $lines[] = ' * @return ' . $return->getContent();
        }
        if (!$hasReturn && $hasReturnInFunction) {
            $lines[] = ' * @return ' . implode('|', $returnType) . ' [[@doctodo return_description:' . $method->getName() . ']]';
        }
        // if (count($currentThrows) > 2) {
        //     \d($currentThrows);
        //     exit;
        // }
        foreach ($currentThrows as $throw) {
            $lines[] = ' * @throws ' . $throw->getContent();
        }
        foreach (['see', 'todo', 'deprecated', 'link', 'since', 'uses', 'var'] as $tagName) {
            foreach ($phpdoc->getTagsByName($tagName) as $tag) {
                $lines[] = ' * @' . $tagName . ' ' . $tag->getContent();
            }
        }
        if ($lines[count($lines)-1] === ' *') {
            unset($lines[count($lines)-1]);
        }
        $lines[] = ' */';

        return $lines;
    }

    /**
     * Get method code.
     */
    public function getMethodCode($method)
    {
        $methodFile = $method->getFileName();
        $contents = file_get_contents($methodFile, true);
        if (!empty($contents)) {
            $contents = preg_split("/\\r\\n|\\r|\\n/", $contents);

            return trim(implode("\n", array_slice($contents, $method->getStartLine()-1, $method->getEndLine()-$method->getStartLine()+1)));
        }

        return false;
    }

    /**
     *
     */
    public function updatePropertyDocs($fileContent, $className, $file)
    {
        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file)) {
            static::stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n");
        }
        $updates = [];
        foreach ($ref->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $startLine = false;
            // \d(explode("\n", file_get_contents($file)));
            foreach (preg_split("/\\r\\n|\\r|\\n/", file_get_contents($file)) as $line => $content) {
                if (preg_match(
                    '/
                        (private|protected|public|var|static|const) # match visibility or var
                        \s                             # followed 1 whitespace
                        [\$]?' . $property->getName() . '                          # followed by the var name $bar
                        [\s|\;]
                    /x',
                    $content)
                ) {
                    $startLine = $line + 1;
                }
            }
            if (!$startLine) {
                continue;
            }
            $inheritDocs = static::isPropertyReplacingParent($ref, $property);
            $docs = $originalDocs = $property->getDocComment();
            $docsSize = count(preg_split("/\\r\\n|\\r|\\n/", $docs));
            if (empty($docs)) {
                $docsSize = 0;
                $docs = "/**\n */";
            }
            $lines = $originalLines = preg_split("/\\r\\n|\\r|\\n/", $docs);
            if ($inheritDocs && $docsSize === 0) {
                array_splice($lines, 1, 0, [' * @inheritdoc']);
            }
            if (preg_match('/\@inheritdoc/', implode($lines)) === 0) {
                $lines = static::generatePropertyDocs($ref, $property, $lines);
            }
            $currentStartLine = $fileContent[$startLine-1];
            preg_match('/^([ \t\r\n\f]*)[a-zA-Z].*/', $currentStartLine, $matches);
            $whitespace = isset($matches[1]) ? $matches[1] : '';
            $newDocs = '';
            // if (isset($lines[0]) && trim($lines[0]) === '/*') {
            //     var_dump($lines);exit;
            // }
            foreach ($lines as $k => $line) {
                $newDocs .= trim($line) . "\n";
            }
            $originalDocs = '';
            foreach ($originalLines as $k => $line) {
                $originalDocs .= trim($line) . "\n";
            }
            foreach ($lines as $k => $line) {
                $extra = '';
                if ($k !== 0) {
                    $extra = ' ';
                }
                $lines[$k] = $whitespace . $extra . trim($line);
            }
            if ($newDocs !== $originalDocs && $newDocs !== "/**\n */") {
                $updates[] = [
                    'inject' => $lines,
                    'length' => $docsSize,
                    'start' => $startLine - 1 - $docsSize,
                ];
            }
        }

        return $updates;
    }

    /**
     *
     */
    public function updateMethodDocs($fileContent, $className, $file)
    {
        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file->getRealPath())) {
            static::stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n");
            return [];
        }
        $updates = [];
        foreach ($ref->getMethods() as $method) {
            // var_dump($method);exit;
            if (strtolower($method->getFileName()) != strtolower($file)) {
                continue;
            }
            $inheritDocs = static::isMethodReplacingParent($ref, $method);
            $docs = $originalDocs = $method->getDocComment();
            $docsSize = count(preg_split("/\\r\\n|\\r|\\n/", $docs));
            if (empty($docs)) {
                $docsSize = 0;
                $docs = "/**\n */";
            }
            $lines = $originalLines = preg_split("/\\r\\n|\\r|\\n/", $docs);
            if ($inheritDocs && $docsSize === 0) {
                array_splice($lines, 1, 0, [' * @inheritdoc']);
            }
            if (preg_match('/\@inheritdoc/', implode($lines)) === 0) {
                $lines = static::generateMethodDocs($method, $lines);
            }
            $currentStartLine = $fileContent[$method->getStartLine()-1];
            preg_match('/^([ \t\r\n\f]*)[a-zA-Z].*/', $currentStartLine, $matches);
            $whitespace = isset($matches[1]) ? $matches[1] : '';
            $newDocs = '';
            foreach ($lines as $k => $line) {
                $newDocs .= trim($line) . "\n";
            }
            $originalDocs = '';
            foreach ($originalLines as $k => $line) {
                $originalDocs .= trim($line) . "\n";
            }
            foreach ($lines as $k => $line) {
                $extra = '';
                if ($k !== 0) {
                    $extra = ' ';
                }
                $lines[$k] = $whitespace . $extra . trim($line);
            }
            if (trim($newDocs) !== trim($originalDocs) && $newDocs !== "/**\n */") {
                $updates[] = [
                    'inject' => $lines,
                    'length' => $docsSize,
                    'start' => $method->getStartLine() - 1 - $docsSize,
                ];
            }
        }

        return $updates;
    }

    /**
     *
     */
    public function isPropertyReplacingParent(\ReflectionClass $class, \ReflectionProperty $property)
    {
        $parentClass = $class->getParentClass();
        if (!$parentClass) {
            return false;
        }
        if (property_exists($parentClass->getName(), $property->getName())) {
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function isMethodReplacingParent(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $parentClass = $class->getParentClass();
        if (!$parentClass) {
            return false;
        }
        if (method_exists($parentClass->getName(), $method->getName())) {
            return true;
        }

        return false;
    }

    /**
     * remove multi empty lines and trim trailing whitespace.
     *
     * @param $doc
     *
     * @return string
     */
    protected function cleanDocComment($doc)
    {
        $lines = preg_split("/\\r\\n|\\r|\\n/", $doc);
        $n = count($lines);
        for ($i = 0; $i < $n; $i++) {
            $lines[$i] = rtrim($lines[$i]);
            if (trim($lines[$i]) == '*' && trim($lines[$i + 1]) == '*') {
                unset($lines[$i]);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Replace property annotations in doc comment.
     *
     * @param $doc
     * @param $properties
     *
     * @return string
     */
    protected function updateDocComment($doc, $properties, $coveredProperties, $ref)
    {
        $lines = explode("\n", trim($doc));
        $propertyPart = false;
        $propertyPosition = false;
        foreach ($lines as $i => $line) {
            if (substr(trim($line), 0, 12) == '* @property ') {
                $propertyPart = true;
            } elseif ($propertyPart && trim($line) == '*') {
                $propertyPosition = $i;
                $propertyPart = false;
            }
            if (substr(trim($line), 0, 10) == '* @author ' && $propertyPosition === false) {
                $propertyPosition = $i - 1;
                $propertyPart = false;
            }
            if ($propertyPart && !$ref->isSubclassOf('yii\base\Model')) {
                unset($lines[$i]);
            } elseif ($ref->isSubclassOf('yii\db\ActiveRecord')
                && preg_match('/^\* This is the model class for table/', trim($line)) === 1) {
                unset($lines[$i]);
            } else {
                foreach ($coveredProperties as $property) {
                    if (preg_match('/^\* \@property[^\w]' . $property . '/', trim($line)) === 1) {
                        unset($lines[$i]);
                        break;
                    }
                }
            }
        }
        $finalDoc = '';
        foreach ($lines as $i => $line) {
            $finalDoc .= $line . "\n";
            if ($i == $propertyPosition) {
                $finalDoc .= $properties;
            }
        }

        return $finalDoc;
    }

    /**
     *
     */
    protected function fixSentence($str)
    {
        // TODO fix word wrap
        if ($str == '') {
            return '';
        }

        return strtoupper(substr($str, 0, 1)) . substr($str, 1) . ($str[strlen($str) - 1] != '.' ? '.' : '');
    }

    /**
     * Get prop param.
     */
    protected function getPropParam($prop, $param)
    {
        return isset($prop['property']) ? $prop['property'][$param] : (isset($prop['get']) ? $prop['get'][$param] : $prop['set'][$param]);
    }

    protected function stderr($err)
    {
        echo $err;
    }
}
