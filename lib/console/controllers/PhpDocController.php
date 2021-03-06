<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

/**
 * PhpDocController is there to help maintaining PHPDoc annotation in class files.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Jacob Morrison <email@ofjacob.com>
 *
 * @since 2.0
 */
class PhpDocController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'property';
    /**
     * @var [[@doctodo var_type:author]] [[@doctodo var_description:author]]
     */
    public $author = "Jacob Morrison <email@ofjacob.com>";
    /**
     * @var [[@doctodo var_type:copyright]] [[@doctodo var_description:copyright]]
     */
    public $copyright = "Copyright (c) 2015 Canis";
    /**
     * @var [[@doctodo var_type:link]] [[@doctodo var_description:link]]
     */
    public $link = "http://canis.io/";
    /**
     * @var [[@doctodo var_type:license]] [[@doctodo var_description:license]]
     */
    public $license = "http://canis.io/license";
    /**
     * @var boolean whether to update class docs directly. Setting this to false will just output docs for copy and paste.
     */
    public $updateFiles = true;

    /**
     * Generates `@property annotations` in class files from getters and setters
     * Property description will be taken from getter or setter or from an `@property annotation`
     * in the getters docblock if there is one defined.
     * See https://github.com/yiisoft/yii2/wiki/Core-framework-code-style#documentation for details.
     *
     * @param string $root the directory to parse files from. Defaults to YII_PATH.
     */
    public function actionProperty($root = null)
    {
        $files = $this->findFiles($root);

        $nFilesTotal = 0;
        $nFilesUpdated = 0;
        foreach ($files as $file) {
            $result = $this->generateClassPropertyDocs($file);
            if ($result !== false) {
                list($className, $phpdoc, $coveredProperties) = $result;
                if ($this->updateFiles) {
                    if ($this->updateClassPropertyDocs($file, $className, $phpdoc, $coveredProperties)) {
                        $nFilesUpdated++;
                    }
                } elseif (!empty($phpdoc)) {
                    $this->stdout("\n[ " . $file . " ]\n\n", Console::BOLD);
                    $this->stdout($phpdoc);
                }
            }
            $nFilesTotal++;
        }

        $this->stdout("\nParsed $nFilesTotal files.\n");
        $this->stdout("Updated $nFilesUpdated files.\n");
    }

    /**
     * Fix some issues with PHPdoc in files.
     *
     * @param string $root the directory to parse files from. Defaults to YII_PATH.
     */
    public function actionFix($root = null)
    {
        $files = $this->findFiles($root);

        $nFilesTotal = 0;
        $nFilesUpdated = 0;
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $sha = sha1($contents);

            // fix line endings
            $lines = preg_split('/(\r\n|\n|\r)/', $contents);

            $this->fixFileDoc($lines, $file);

            $newContent = implode("\n", $lines);
            if ($sha !== sha1($newContent)) {
                $nFilesUpdated++;
            }
            file_put_contents($file, $newContent);
            $nFilesTotal++;
        }

        $this->stdout("\nParsed $nFilesTotal files.\n");
        $this->stdout("Updated $nFilesUpdated files.\n");
    }

    /**
     * @inheritdoc
     */
    public function options($actionId)
    {
        return array_merge(parent::options($actionId), ['updateFiles']);
    }

    /**
     * [[@doctodo method_description:findFiles]].
     *
     * @param [[@doctodo param_type:root]] $root [[@doctodo param_description:root]]
     *
     * @return [[@doctodo return_type:findFiles]] [[@doctodo return_description:findFiles]]
     */
    protected function findFiles($root)
    {
        $except = [];
        if ($root === null) {
            $root = CANIS_APP_VENDOR_PATH . DIRECTORY_SEPARATOR . 'canis';//. DIRECTORY_SEPARATOR .'cascade-lib';

            $except = [
                '.git/',
                '/apps/',
                '/build/',
                '/docs/',
                '/extensions/apidoc/helpers/PrettyPrinter.php',
                '/extensions/codeception/TestCase.php',
                '/extensions/codeception/DbTestCase.php',
                '/extensions/composer/',
                '/extensions/gii/components/DiffRendererHtmlInline.php',
                '/extensions/gii/generators/extension/default/*',
                '/extensions/twig/TwigSimpleFileLoader.php',
                '/framework/BaseYii.php',
                '/framework/Yii.php',
                'tests/',
                'vendor/',
                'cs/',
            ];
        }
        $root = FileHelper::normalizePath($root);
        $options = [
            'filter' => function ($path) {
                    if (is_file($path)) {
                        $file = basename($path);
                        if ($file[0] < 'A' || $file[0] > 'Z') {
                            return false;
                        }
                    }

                    return;
                },
            'only' => ['*.php'],
            'except' => array_merge($except, [
                'views/',
                'requirements/',
                'gii/generators/',
                'vendor/',
            ]),
        ];

        return FileHelper::findFiles($root, $options);
    }

    /**
     * [[@doctodo method_description:fixFileDoc]].
     *
     * @param [[@doctodo param_type:lines]] $lines [[@doctodo param_description:lines]]
     * @param [[@doctodo param_type:file]]  $file  [[@doctodo param_description:file]]
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
            $lines = array_merge([
                "<?php",
                "/**",
                " * @link " . $this->link,
                " * @copyright " . $this->copyright,
                " * @license " . $this->license,
                " */",
                "",
                $namespaceLine,
                "",
            ], $lines);
        }
    }

    /**
     * [[@doctodo method_description:guessClassDescription]].
     *
     * @param [[@doctodo param_type:ref]] $ref [[@doctodo param_description:ref]]
     *
     * @return [[@doctodo return_type:guessClassDescription]] [[@doctodo return_description:guessClassDescription]]
     */
    protected function guessClassDescription($ref)
    {
        $description = '__class_' . $ref->getName() . '_description__';
        if ($ref->isSubclassOf('yii\db\ActiveRecord')) {
            $className = $ref->getName();
            $tableName = $className::tableName();
            $description = "is the model class for table \"{$tableName}\".";
        }

        return $description;
    }

    /**
     * [[@doctodo method_description:updateClassPropertyDocs]].
     *
     * @param [[@doctodo param_type:file]]              $file              [[@doctodo param_description:file]]
     * @param [[@doctodo param_type:className]]         $className         [[@doctodo param_description:className]]
     * @param [[@doctodo param_type:propertyDoc]]       $propertyDoc       [[@doctodo param_description:propertyDoc]]
     * @param [[@doctodo param_type:coveredProperties]] $coveredProperties [[@doctodo param_description:coveredProperties]]
     *
     * @return [[@doctodo return_type:updateClassPropertyDocs]] [[@doctodo return_description:updateClassPropertyDocs]]
     */
    protected function updateClassPropertyDocs($file, $className, $propertyDoc, $coveredProperties)
    {
        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file)) {
            $this->stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n", Console::FG_RED);
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
            $newDoc = $this->cleanDocComment($this->updateDocComment($oldDoc, $propertyDoc, $coveredProperties, $ref));
        }
        $seenSince = false;
        $seenAuthor = false;

        // TODO move these checks to different action
        $lines = explode("\n", trim($newDoc));

        $oldTest = ' * ' . $ref->getShortName();
        if (strpos($lines[1], $oldTest) !== 0) {
            array_splice($lines, 1, 0, [" * " . $ref->getShortName() . " " . $this->guessClassDescription($ref), ' *']);
        }

        if (trim($lines[1]) == '*' || substr(trim($lines[1]), 0, 3) == '* @') {
            $this->stderr("[WARN] Class $className has no short description.\n", Console::FG_YELLOW, Console::BOLD);
        }
        foreach ($lines as $line) {
            if (substr(trim($line), 0, 9) == '* @since ') {
                $seenSince = true;
            } elseif (substr(trim($line), 0, 10) == '* @author ') {
                $seenAuthor = true;
            }
        }

        if (!$seenSince) {
            //$this->stderr("[ERR] No @since found in class doc in file: $file\n", Console::FG_RED);
        }
        if (!$seenAuthor) {
            $insertLines = [];
            if (strpos($lines[count($lines)-2], " * @since") === false
                && $lines[count($lines)-2] !== ' *') {
                $insertLines[] = ' *';
            }
            $insertLines[] = " * @author {$this->author}";
            array_splice($lines, count($lines)-1, 0, $insertLines);
            // $this->stderr("[ERR] No @author found in class doc in file: $file\n", Console::FG_RED);
        }
        $newDoc = implode("\n", $lines) . "\n";
        $updates = [];
        $fileContent = explode("\n", file_get_contents($file));
        if (trim($oldDoc) != trim($newDoc)) {
            $start = $startLine;
            $updates[] = [
                'inject' => $lines,
                'length' => $oldDocSize,
                'start' => $start,
            ];
        }
        $updates = array_merge($updates, $this->updateMethodDocs($fileContent, $className, $file));
        $updates = array_merge($updates, $this->updatePropertyDocs($fileContent, $className, $file));
        ArrayHelper::multisort($updates, 'start', SORT_ASC, SORT_NUMERIC);
        $offset = 0;
        if (!empty($updates)) {
            foreach ($updates as $update) {
                array_splice($fileContent, $update['start'] + $offset, $update['length'], $update['inject']);
                $offset = $offset + (count($update['inject']) - $update['length']);
            }
            file_put_contents($file, implode("\n", $fileContent));
        }

        return !empty($updates);
    }

    /**
     * [[@doctodo method_description:guessMethodDescription]].
     *
     * @param [[@doctodo param_type:method]] $method [[@doctodo param_description:method]]
     *
     * @return [[@doctodo return_type:guessMethodDescription]] [[@doctodo return_description:guessMethodDescription]]
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

        return '__method_' . $method->getName() . '_description__';
    }

    /**
     * [[@doctodo method_description:generatePropertyDocs]].
     *
     * @param [[@doctodo param_type:class]]    $class    [[@doctodo param_description:class]]
     * @param [[@doctodo param_type:property]] $property [[@doctodo param_description:property]]
     * @param [[@doctodo param_type:lines]]    $lines    [[@doctodo param_description:lines]]
     *
     * @return [[@doctodo return_type:generatePropertyDocs]] [[@doctodo return_description:generatePropertyDocs]]
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
            $type = '__var_' . $property->getName() . '_type__';
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
                $type . ' $' . $property->getName() . ' __var_' . $property->getName() . '_description__'
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
     * [[@doctodo method_description:generateMethodDocs]].
     *
     * @param [[@doctodo param_type:method]] $method [[@doctodo param_description:method]]
     * @param [[@doctodo param_type:lines]]  $lines  [[@doctodo param_description:lines]]
     *
     * @return [[@doctodo return_type:generateMethodDocs]] [[@doctodo return_description:generateMethodDocs]]
     */
    public function generateMethodDocs($method, $lines)
    {
        if (trim($lines[1]) == '*' || substr(trim($lines[1]), 0, 3) == '* @') {
            array_splice($lines, 1, 0, [" * " . $this->guessMethodDescription($method), ' *']);
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
                $type = '__param_' . $param->getName() . '_type__';
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
                    $type . ' $' . $param->getName() . ' __param_' . $param->getName() . '_description__' . $extra
                );
                $currentParams[$paramObject->getVariableName()] = $paramObject;
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
        if (!isset($lineDescription) || trim($lineDescription) === '* __method_' . $method->getName() . '_description__') {
            $lineDescription = ' * ' . $this->guessMethodDescription($method);
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
                }
            }
        }
        $hasReturnInFunction = false;
        $methodCode = $this->getMethodCode($method);
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
                            $currentThrows[$type] = new \phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag('throws', $type . ' __exception_' . $type . '_description__');
                        }
                    break;
                }
            }
        }
        if (empty($returnType)) {
            $returnType = ['__return_' . $method->getName() . '_type__'];
        }
        $hasReturn = false;
        foreach ($currentReturn as $return) {
            $hasReturn = true;
            $lines[] = ' * @return ' . $return->getContent();
        }
        if (!$hasReturn && $hasReturnInFunction) {
            $lines[] = ' * @return ' . implode('|', $returnType) . ' __return_' . $method->getName() . '_description__';
        }
        if (count($currentThrows) > 2) {
            \d($currentThrows);
            exit;
        }
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
     *
     * @param [[@doctodo param_type:method]] $method [[@doctodo param_description:method]]
     *
     * @return [[@doctodo return_type:getMethodCode]] [[@doctodo return_description:getMethodCode]]
     */
    public function getMethodCode($method)
    {
        $methodFile = $method->getFileName();
        $contents = file_get_contents($methodFile, true);
        if (!empty($contents)) {
            $contents = explode("\n", $contents);

            return trim(implode("\n", array_slice($contents, $method->getStartLine()-1, $method->getEndLine()-$method->getStartLine()+1)));
        }

        return false;
    }

    /**
     * [[@doctodo method_description:updatePropertyDocs]].
     *
     * @param [[@doctodo param_type:fileContent]] $fileContent [[@doctodo param_description:fileContent]]
     * @param [[@doctodo param_type:className]]   $className   [[@doctodo param_description:className]]
     * @param [[@doctodo param_type:file]]        $file        [[@doctodo param_description:file]]
     *
     * @return [[@doctodo return_type:updatePropertyDocs]] [[@doctodo return_description:updatePropertyDocs]]
     */
    public function updatePropertyDocs($fileContent, $className, $file)
    {
        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file)) {
            $this->stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n", Console::FG_RED);
        }
        $updates = [];
        foreach ($ref->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $startLine = false;
            // \d(explode("\n", file_get_contents($file)));
            foreach (explode("\n", file_get_contents($file)) as $line => $content) {
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
            $inheritDocs = $this->isPropertyReplacingParent($ref, $property);
            $docs = $originalDocs = $property->getDocComment();
            $docsSize = count(explode("\n", $docs));
            if (empty($docs)) {
                $docsSize = 0;
                $docs = "/**\n */";
            }
            $lines = $originalLines = explode("\n", $docs);
            if ($inheritDocs && $docsSize === 0) {
                array_splice($lines, 1, 0, [' * @inheritdoc']);
            }
            if (preg_match('/\@inheritdoc/', implode($lines)) === 0) {
                $lines = $this->generatePropertyDocs($ref, $property, $lines);
            }
            $currentStartLine = $fileContent[$startLine-1];
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
     * [[@doctodo method_description:updateMethodDocs]].
     *
     * @param [[@doctodo param_type:fileContent]] $fileContent [[@doctodo param_description:fileContent]]
     * @param [[@doctodo param_type:className]]   $className   [[@doctodo param_description:className]]
     * @param [[@doctodo param_type:file]]        $file        [[@doctodo param_description:file]]
     *
     * @return [[@doctodo return_type:updateMethodDocs]] [[@doctodo return_description:updateMethodDocs]]
     */
    public function updateMethodDocs($fileContent, $className, $file)
    {
        $ref = new \ReflectionClass($className);
        if (strtolower($ref->getFileName()) != strtolower($file)) {
            $this->stderr("[ERR] Unable to create ReflectionClass for class: $className loaded class ({$ref->getFileName()}) is not from file: $file\n", Console::FG_RED);
        }
        $updates = [];
        foreach ($ref->getMethods() as $method) {
            if (strtolower($method->getFileName()) != strtolower($file)) {
                continue;
            }
            $inheritDocs = $this->isMethodReplacingParent($ref, $method);
            $docs = $originalDocs = $method->getDocComment();
            $docsSize = count(explode("\n", $docs));
            if (empty($docs)) {
                $docsSize = 0;
                $docs = "/**\n */";
            }
            $lines = $originalLines = explode("\n", $docs);
            if ($inheritDocs && $docsSize === 0) {
                array_splice($lines, 1, 0, [' * @inheritdoc']);
            }
            if (preg_match('/\@inheritdoc/', implode($lines)) === 0) {
                $lines = $this->generateMethodDocs($method, $lines);
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
     * [[@doctodo method_description:isPropertyReplacingParent]].
     *
     * @param ReflectionClass    $class    [[@doctodo param_description:class]]
     * @param ReflectionProperty $property [[@doctodo param_description:property]]
     *
     * @return [[@doctodo return_type:isPropertyReplacingParent]] [[@doctodo return_description:isPropertyReplacingParent]]
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
     * [[@doctodo method_description:isMethodReplacingParent]].
     *
     * @param ReflectionClass  $class  [[@doctodo param_description:class]]
     * @param ReflectionMethod $method [[@doctodo param_description:method]]
     *
     * @return [[@doctodo return_type:isMethodReplacingParent]] [[@doctodo return_description:isMethodReplacingParent]]
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
        $lines = explode("\n", $doc);
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
     * @param [[@doctodo param_type:coveredProperties]] $coveredProperties [[@doctodo param_description:coveredProperties]]
     * @param [[@doctodo param_type:ref]]               $ref               [[@doctodo param_description:ref]]
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
     * [[@doctodo method_description:generateClassPropertyDocs]].
     *
     * @param [[@doctodo param_type:fileName]] $fileName [[@doctodo param_description:fileName]]
     *
     * @return [[@doctodo return_type:generateClassPropertyDocs]] [[@doctodo return_description:generateClassPropertyDocs]]
     */
    protected function generateClassPropertyDocs($fileName)
    {
        $phpdoc = "";
        $file = str_replace("\r", "", str_replace("\t", " ", file_get_contents($fileName, true)));
        $ns = $this->match('#\nnamespace (?<name>[\w\\\\]+);\n#', $file);
        $namespace = reset($ns);
        $namespace = $namespace['name'];
        $classes = $this->match('#\n(?:abstract )?class (?<name>\w+)( extends .+)?( implements .+)?\n\{(?<content>.*)\n\}(\n|$)#', $file);

        if (count($classes) > 1) {
            $this->stderr("[ERR] There should be only one class in a file: $fileName\n", Console::FG_RED);

            return false;
        }
        if (count($classes) < 1) {
            $interfaces = $this->match('#\ninterface (?<name>\w+)( extends .+)?\n\{(?<content>.+)\n\}(\n|$)#', $file);
            if (count($interfaces) == 1) {
                return false;
            } elseif (count($interfaces) > 1) {
                $this->stderr("[ERR] There should be only one interface in a file: $fileName\n", Console::FG_RED);
            } else {
                $traits = $this->match('#\ntrait (?<name>\w+)\n\{(?<content>.+)\n\}(\n|$)#', $file);
                if (count($traits) == 1) {
                    return false;
                } elseif (count($traits) > 1) {
                    $this->stderr("[ERR] There should be only one class/trait/interface in a file: $fileName\n", Console::FG_RED);
                } else {
                    $this->stderr("[ERR] No class in file: $fileName\n", Console::FG_RED);
                }
            }

            return false;
        }

        $className = null;
        foreach ($classes as &$class) {
            $className = $namespace . '\\' . $class['name'];

            $gets = $this->match(
                '#\* @return (?<type>[\w\\|\\\\\\[\\]]+)(?: (?<comment>(?:(?!\*/|\* @).)+?)(?:(?!\*/).)+|[\s\n]*)\*/' .
                '[\s\n]{2,}public function (?<kind>get)(?<name>\w+)\((?:,? ?\$\w+ ?= ?[^,]+)*\)#',
                $class['content']);
            $sets = $this->match(
                '#\* @param (?<type>[\w\\|\\\\\\[\\]]+) \$\w+(?: (?<comment>(?:(?!\*/|\* @).)+?)(?:(?!\*/).)+|[\s\n]*)\*/' .
                '[\s\n]{2,}public function (?<kind>set)(?<name>\w+)\(\$\w+(?:, ?\$\w+ ?= ?[^,]+)*\)#',
                $class['content']);
            // check for @property annotations in getter and setter
            $properties = $this->match(
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
                    'comment' => $this->fixSentence($acr['comment']),
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
                    $docline .= ' ' . $this->getPropParam($prop, 'type') . " $$propName ";
                    $comment = explode("\n", $this->getPropParam($prop, 'comment') . $note);
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
     * [[@doctodo method_description:match]].
     *
     * @param [[@doctodo param_type:pattern]] $pattern [[@doctodo param_description:pattern]]
     * @param [[@doctodo param_type:subject]] $subject [[@doctodo param_description:subject]]
     *
     * @return [[@doctodo return_type:match]] [[@doctodo return_description:match]]
     */
    protected function match($pattern, $subject)
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
     * [[@doctodo method_description:fixSentence]].
     *
     * @param [[@doctodo param_type:str]] $str [[@doctodo param_description:str]]
     *
     * @return [[@doctodo return_type:fixSentence]] [[@doctodo return_description:fixSentence]]
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
     *
     * @param [[@doctodo param_type:prop]]  $prop  [[@doctodo param_description:prop]]
     * @param [[@doctodo param_type:param]] $param [[@doctodo param_description:param]]
     *
     * @return [[@doctodo return_type:getPropParam]] [[@doctodo return_description:getPropParam]]
     */
    protected function getPropParam($prop, $param)
    {
        return isset($prop['property']) ? $prop['property'][$param] : (isset($prop['get']) ? $prop['get'][$param] : $prop['set'][$param]);
    }
}
