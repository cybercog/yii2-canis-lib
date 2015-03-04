<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace infinite\debug;

use Yii;
use yii\debug\Panel;
use yii\debug\models\search\Profile;

/**
 * Debugger panel that collects and displays performance profiling info.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class ProfilingSummaryPanel extends Panel
{
    /**
     * @var array current request profile timings
     */
    private $_models;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Profiling Summary';
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        $searchModel = new Profile();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams(), $this->getModels());

        return Yii::$app->view->render('@infinite/views/debug/profilingSummary/detail', [
            'panel' => $this,
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'memory' => sprintf('%.1f MB', $this->data['memory'] / 1048576),
            'time' => number_format($this->data['time'] * 1000).' ms',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $target = $this->module->logTarget;

        return [
            'memory' => memory_get_peak_usage(),
            'time' => microtime(true) - YII_BEGIN_TIME,
        ];
    }

    /**
     * Returns array of profiling models that can be used in a data provider.
     *
     * @return array models
     */
    protected function getModels()
    {
        if ($this->_models === null) {
            $this->_models = [];
            $a = [];
            $timings = Yii::getLogger()->calculateTimings($this->data['messages']);

            foreach ($timings as $seq => $profileTiming) {
                $key = md5($profileTiming['category'].$profileTiming['info']);
                if (!isset($a[$key])) {
                    $a[$key] =  [
                        'durations' => [], // in milliseconds
                        'category' => $profileTiming['category'],
                        'info' => $profileTiming['info'],
                        'level' => $profileTiming['level'],
                        'seq' => $seq,
                    ];
                }
                $a[$key]['durations'][] = $profileTiming['duration'] * 1000;
            }

            foreach (array_values($a) as $seq => $profileTiming) {
                $this->_models[] =  [
                    'maxDuration' => max($profileTiming['durations']), // in milliseconds
                    'minDuration' => min($profileTiming['durations']), // in milliseconds
                    'avgDuration' => array_sum($profileTiming['durations']) / count($profileTiming['durations']), // in milliseconds
                    'totalDuration' => array_sum($profileTiming['durations']), // in milliseconds
                    'number' => count($profileTiming['durations']),
                    'category' => $profileTiming['category'],
                    'info' => $profileTiming['info'],
                    'level' => $profileTiming['level'],
                    'seq' => $seq,
                ];
            }
        }

        return $this->_models;
    }
}
