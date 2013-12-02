<?php
/**
 * library/web/User.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\web;

class View extends \yii\web\View
{
	public $bodyHtmlOptions = [];
	
    public function registerJsFile($url, $options = [], $key = null)
    {
        // @todo hopefully this won't be necessary in the future
        if (is_null($key)) {
            $key = basename($url);
        }
        return parent::registerJsFile($url, $options, $key);
    }
}
