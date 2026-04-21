<?php

declare(strict_types=1);

use yii\grid\GridView;
use yii\helpers\Html;

/**
 * @var \yii\data\ActiveDataProvider $dataProvider Data provider for the users grid.
 * @var \app\models\UserSearch $searchModel Search model bound to the filter row.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Users';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        'id',
        'username',
        'email',
        'status',
        'created_at:datetime',
    ],
]) ?>
