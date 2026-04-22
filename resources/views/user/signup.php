<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var \app\models\SignupForm $model Signup form model.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Sign up';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm('', 'post') ?>
<p>
    <?= Html::activeLabel($model, 'username', ['label' => 'Username']) ?>
    <?= Html::activeTextInput($model, 'username') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'email', ['label' => 'Email']) ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'password', ['label' => 'Password']) ?>
    <?= Html::activePasswordInput($model, 'password') ?>
</p>
<p>
    <?= Html::submitButton('Sign up') ?>
</p>
<?= Html::endForm() ?>
