<?php
namespace canis\action;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Action [[@doctodo class_description:canis\action\Action]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Action extends \canis\base\Object implements InteractiveActionInterface
{
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:_interactions]] [[@doctodo var_description:_interactions]]
     */
    protected $_interactions = [];
    /**
     * @var [[@doctodo var_type:_config]] [[@doctodo var_description:_config]]
     */
    protected $_config;

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }

        return $this->_id;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
     */
    public function save()
    {
        return true;
    }

    /**
     * [[@doctodo method_description:cancel]].
     *
     * @return [[@doctodo return_type:cancel]] [[@doctodo return_description:cancel]]
     */
    public function cancel()
    {
        return true;
    }

    /**
     * [[@doctodo method_description:packageData]].
     *
     * @param boolean $details [[@doctodo param_description:details]] [optional]
     *
     * @return [[@doctodo return_type:packageData]] [[@doctodo return_description:packageData]]
     */
    public function packageData($details = false)
    {
        $d = [];
        $d['interactions'] = $this->interactionsPackage;

        return $d;
    }

    /**
     * Get interactions.
     *
     * @return [[@doctodo return_type:getInteractions]] [[@doctodo return_description:getInteractions]]
     */
    public function getInteractions()
    {
        return $this->_interactions;
    }

    /**
     * [[@doctodo method_description:hasInteractions]].
     *
     * @return [[@doctodo return_type:hasInteractions]] [[@doctodo return_description:hasInteractions]]
     */
    public function hasInteractions()
    {
        return !empty($this->_interactions);
    }

    /**
     * [[@doctodo method_description:resolveInteractions]].
     */
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

    /**
     * [[@doctodo method_description:createInteraction]].
     *
     * @param [[@doctodo param_type:label]]    $label     [[@doctodo param_description:label]]
     * @param [[@doctodo param_type:options]]  $options   [[@doctodo param_description:options]]
     * @param [[@doctodo param_type:callback]] $callback  [[@doctodo param_description:callback]]
     * @param boolean                          $handleNow [[@doctodo param_description:handleNow]] [optional]
     *
     * @return [[@doctodo return_type:createInteraction]] [[@doctodo return_description:createInteraction]]
     */
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

    /**
     * [[@doctodo method_description:handleInteractions]].
     *
     * @param integer $sleep [[@doctodo param_description:sleep]] [optional]
     *
     * @return [[@doctodo return_type:handleInteractions]] [[@doctodo return_description:handleInteractions]]
     */
    public function handleInteractions($sleep = 30)
    {
        return true;
    }

    /**
     * [[@doctodo method_description:pauseAction]].
     *
     * @return [[@doctodo return_type:pauseAction]] [[@doctodo return_description:pauseAction]]
     */
    public function pauseAction()
    {
        return true;
    }

    /**
     * [[@doctodo method_description:resumeAction]].
     *
     * @return [[@doctodo return_type:resumeAction]] [[@doctodo return_description:resumeAction]]
     */
    public function resumeAction()
    {
        return true;
    }

    /**
     * Get interactions package.
     *
     * @return [[@doctodo return_type:getInteractionsPackage]] [[@doctodo return_description:getInteractionsPackage]]
     */
    public function getInteractionsPackage()
    {
        if (empty($this->_interactions)) {
            return false;
        }
        $p = [];
        foreach ($this->_interactions as $key => $interaction) {
            if ($interaction->resolved) {
                continue;
            }
            $p[$key] = $interaction->package();
        }

        return $p;
    }

    /**
     * Set config.
     *
     * @param [[@doctodo param_type:config]] $config [[@doctodo param_description:config]]
     */
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

    /**
     * Get config.
     *
     * @return [[@doctodo return_type:getConfig]] [[@doctodo return_description:getConfig]]
     */
    public function getConfig()
    {
        if (!isset($this->_config)) {
            return [];
        }

        return $this->_config;
    }

    /**
     * [[@doctodo method_description:checkParams]].
     *
     * @param boolean $fatal [[@doctodo param_description:fatal]] [optional]
     *
     * @throws InvalidConfigException [[@doctodo exception_description:InvalidConfigException]]
     * @return [[@doctodo return_type:checkParams]] [[@doctodo return_description:checkParams]]
     *
     */
    public function checkParams($fatal = true)
    {
        foreach ($this->requiredConfigParams() as $param) {
            if (!isset($this->config[$param])) {
                if ($fatal) {
                    throw new InvalidConfigException("Config setting {$param} is required for " . get_called_class());
                }

                return false;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:requiredConfigParams]].
     *
     * @return [[@doctodo return_type:requiredConfigParams]] [[@doctodo return_description:requiredConfigParams]]
     */
    public function requiredConfigParams()
    {
        return [];
    }
}
