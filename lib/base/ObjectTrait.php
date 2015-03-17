<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use ReflectionClass;

trait ObjectTrait
{
    protected $_m;
    public function init()
    {
        parent::init();
    }

    public function clearMemoryId()
    {
        $this->_m = null;
    }

    public function getMemoryId()
    {
        if (is_null($this->_m)) {
            $this->_m = self::classNamespace() . ':' . md5(microtime() . mt_rand());
        }

        return $this->_m;
    }
    public function configure($settings)
    {
        foreach ($settings as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public static function classNamespace()
    {
        $reflector = new ReflectionClass(get_called_class());

        return $reflector->getNamespaceName();
    }

    public static function classFilePath()
    {
        $reflector = new ReflectionClass(get_called_class());

        return $reflector->getFileName();
    }

    public static function baseClassName()
    {
        $reflector = new ReflectionClass(get_called_class());

        return $reflector->getShortName();
    }

    /**
     * Evaluates a PHP expression or callback under the context of this component.
     *
     * Valid PHP callback can be class method name in the form of
     * array(ClassName/Object, MethodName), or anonymous function (only available in PHP 5.3.0 or above).
     *
     * If a PHP callback is used, the corresponding function/method signature should be
     * <pre>
     * function foo($param1, $param2, ..., $component) { ... }
     * </pre>
     * where the array elements in the second parameter to this method will be passed
     * to the callback as $param1, $param2, ...; and the last parameter will be the component itself.
     *
     * If a PHP expression is used, the second parameter will be "extracted" into PHP variables
     * that can be directly accessed in the expression. See {@link http://us.php.net/manual/en/function.extract.php PHP extract}
     * for more details. In the expression, the component object can be accessed using $this.
     *
     * @param mixed $_expression_ a PHP expression or PHP callback to be evaluated.
     * @param array $_data_       additional parameters to be passed to the above expression/callback.
     *
     * @return mixed the expression result
     *
     *                            @since 1.1.0
     */
    public function evaluateExpression($_expression_, $_data_ = [])
    {
        if (is_string($_expression_)) {
            extract($_data_);

            return eval('return ' . $_expression_ . ';');
        } else {
            $_data_[] = $this;

            return call_user_func_array($_expression_, $_data_);
        }
    }
}
