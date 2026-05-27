<?php

declare(strict_types=1);

use app\usecases\user\UserSearch;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var ActiveDataProvider $dataProvider Data provider for the users grid.
 * @var UserSearch $searchModel Search model bound to the filter row.
 * @var View $this View component instance.
 */
$this->title = 'Users';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= GridView::widget(
    [
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'username',
            'email',
            'status',
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'filter' => false,
            ],
        ],
    ],
);
