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
    <?= Html::label('Username', 'signupform-username') ?>
    <?= Html::activeTextInput($model, 'username') ?>
</p>
<p>
    <?= Html::label('Email', 'signupform-email') ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::label('Password', 'signupform-password') ?>
    <?= Html::activePasswordInput($model, 'password') ?>
</p>
<p>
    <?= Html::submitButton('Sign up') ?>
</p>
<?= Html::endForm() ?>
