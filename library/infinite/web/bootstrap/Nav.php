<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\bootstrap;

use infinite\helpers\Html;
/*
 * SubNavBar renders a sub-navbar HTML component.
 *
 * Any content enclosed between the [[begin()]] and [[end()]] calls of NavBar
 * is treated as the content of the navbar. You may use widgets such as [[Nav]]
 * or [[\yii\widgets\Menu]] to build up such content. For example,
 *
 * @see http://twitter.github.io/bootstrap/components.html#navbar
 * @author Antonio Ramirez <amigo.cobos@gmail.com>; Jacob Morrison <jacob@infinitecascade.org>
 */
use Yii;

class Nav extends \yii\bootstrap\Nav
{
    protected $_routeParts;
    public function getRouteParts()
    {
        if (!isset($this->_routeParts) && isset($this->route)) {
            $this->_routeParts = explode('/', ltrim($this->route, '/'));
            if ($this->_routeParts[count($this->_routeParts)-1] === 'index') {
                array_pop($this->_routeParts);
            }
        }
        if (!isset($this->_routeParts)) {
            return [];
        }

        return $this->_routeParts;
    }

    protected function isItemActive($item)
    {
        if (isset($item['url']) && is_array($item['url']) && isset($item['url'][0])) {
            if (isset($item['activeChildren'])) {
                $itemRoute = ltrim($item['activeChildren'], '/');
            } else {
                // item route
                $itemRoute = ltrim($item['url'][0], '/');
            }
            $itemRouteParts = explode('/', $itemRoute);
            if ((empty($itemRouteParts) || $itemRouteParts[0] !== '') && Yii::$app->controller) {
                $itemRoute = Yii::$app->controller->module->getUniqueId() . '/' . $itemRoute;
                $itemRoute = ltrim($itemRoute, '/');
                $itemRouteParts = explode('/', $itemRoute);
            }
            if ($itemRouteParts[count($itemRouteParts)-1] === 'index') {
                array_pop($itemRouteParts);
            }
            $itemRoute = implode('/', $itemRouteParts);
            $requestRoute = implode('/', $this->routeParts);
            if (!empty($item['activeChildren']) && substr($requestRoute, 0, strlen($itemRoute)) !== $itemRoute) {
                return false;
            } elseif (empty($item['activeChildren']) && $itemRoute !== $requestRoute) {
                return false;
            }
            unset($item['url']['#']);
            if (count($item['url']) > 1) {
                foreach (array_splice($item['url'], 1) as $name => $value) {
                    if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }
}
