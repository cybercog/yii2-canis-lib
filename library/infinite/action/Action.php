<?php
namespace infinite\action;

use Yii;
use infinite\deferred\models\DeferredAction;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

abstract class Action extends \infinite\base\Object implements InteractiveActionInterface
{
    protected $_id;
	protected $_interactions = [];
    protected $_config;

    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }
        return $this->_id;
    }


    public function save()
    {
        return true;
    }

	public function cancel()
	{
		return true;
	}

	public function packageData($details = false)
	{
		$d = [];
        $d['interactions'] = $this->interactionsPackage;
		return $d;
	}


    public function getInteractions()
    {
        return $this->_interactions;
    }
    
    public function hasInteractions()
    {
        return !empty($this->_interactions);
    }

    protected function resolveInteractions()
    {
        $resolved = false;
        foreach ($this->_interactions as $id => $interaction) {
            if ($interaction->attemptResolution()) {
                unset($this->_interactions[$id]);
                $resolved = true;
            }
        }
        if ($resolved) {
            $this->save();
        }
    }

    public function createInteraction($label, $options, $callback, $handleNow = true)
    {
        $interaction = ['class' => Interaction::className()];
        $interaction['label'] = $label;
        $interaction['inputType'] = isset($options['inputType']) ? $options['inputType'] : 'text';
        $interaction['details'] = isset($options['details']) ? $options['details'] : null;
        unset($options['inputType'], $options['details']);
        $interaction['options'] = $options;
        $interaction['callback'] = $callback;
        $interaction = Yii::createObject($interaction);
        $this->_interactions[$interaction->id] = $interaction;
        $this->save();
        if ($handleNow) {
            $this->handleInteractions();
        }
        return $interaction;
    }

    public function handleInteractions($sleep = 30)
    {
        return true;
    }

    public function pauseAction()
    {
        return true;
    }

    public function resumeAction()
    {
        return true;
    }

    public function getInteractionsPackage()
    {
        if (empty($this->_interactions)) {
            return false;
        }
        $p = [];
        foreach ($this->_interactions as $key => $interaction) {
            if ($interaction->resolved) { continue; }
            $p[$key] = $interaction->package();
        }
        return $p;
    }

    public function setConfig($config)
	{
		$checkParams = false;
		if (!isset($this->_config)) {
			$checkParams = true;
		}
		$this->_config = $config;
		if ($checkParams) {
			$this->checkParams($this->configFatal);
		}
	}

	public function getConfig()
	{
		if (!isset($this->_config)) {
			return [];
		}
		return $this->_config;
	}

	public function checkParams($fatal = true)
	{
		foreach ($this->requiredConfigParams() as $param) {
			if (!isset($this->config[$param])) {
				if ($fatal) {
					throw new InvalidConfigException("Config setting {$param} is required for ". get_called_class());
				}
				return false;
			}
		}
		return true;
	}

    public function requiredConfigParams()
	{
		return [];
	}
}
?>
