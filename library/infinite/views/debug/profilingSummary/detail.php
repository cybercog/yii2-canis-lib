<?php
/* @var $panel yii\debug\panels\ProfilingPanel */
/* @var $searchModel yii\debug\models\search\Profile */
/* @var $dataProvider yii\data\ArrayDataProvider */
/* @var $time integer */
/* @var $memory integer */

use yii\grid\GridView;
use yii\helpers\Html;

?>
<h1>Performance Summary Profiling</h1>
<p>Total processing time: <b><?= $time ?></b>; Peak memory: <b><?= $memory ?></b>.</p>
<?php
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'id' => 'profile-panel-detailed-grid',
    'options' => ['class' => 'detail-grid-view'],
    'filterModel' => $searchModel,
    'filterUrl' => $panel->getUrl(),
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        'category',
        [
            'attribute' => 'info',
            'value' => function ($data) {
                return str_repeat('<span class="indent">→</span>', $data['level']) . Html::encode($data['info']);
            },
            'format' => 'html',
            'options' => [
                'width' => '60%',
            ],
        ],
        [
            'attribute' => 'avgDuration',
            'value' => function ($data) {
                return sprintf('%.1f ms', $data['avgDuration']);
            },
            'options' => [
                'width' => '10%',
            ],
            'headerOptions' => [
                'class' => 'sort-numerical',
            ],
        ],
        [
            'attribute' => 'maxDuration',
            'value' => function ($data) {
                return sprintf('%.1f ms', $data['maxDuration']);
            },
            'options' => [
                'width' => '10%',
            ],
            'headerOptions' => [
                'class' => 'sort-numerical',
            ],
        ],
        [
            'attribute' => 'minDuration',
            'value' => function ($data) {
                return sprintf('%.1f ms', $data['minDuration']);
            },
            'options' => [
                'width' => '10%',
            ],
            'headerOptions' => [
                'class' => 'sort-numerical',
            ],
        ],
        [
            'attribute' => 'totalDuration',
            'value' => function ($data) {
                return sprintf('%.1f ms', $data['totalDuration']);
            },
            'options' => [
                'width' => '10%',
            ],
            'headerOptions' => [
                'class' => 'sort-numerical',
            ],
        ],
        'number',

    ],
]);
