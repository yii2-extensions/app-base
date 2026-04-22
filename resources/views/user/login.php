<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var \app\models\LoginForm $model Login form model.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Login';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm('', 'post') ?>
<p>
    <?= Html::activeLabel($model, 'username', ['label' => 'Username']) ?>
    <?= Html::activeTextInput($model, 'username') ?>
</p>
<p>
    <?= Html::activeLabel($model, 'password', ['label' => 'Password']) ?>
    <?= Html::activePasswordInput($model, 'password') ?>
</p>
<p>
    <?= Html::activeCheckbox($model, 'rememberMe') ?>
</p>
<p>
    <?= Html::submitButton('Login') ?>
</p>
<?= Html::endForm() ?>
<p>
    <a href="<?= Yii::$app->urlManager->createUrl(['user/request-password-reset']) ?>">Forgot your password?</a>
</p>
<p>
    <a href="<?= Yii::$app->urlManager->createUrl(['user/resend-verification-email']) ?>">Resend verification email</a>
</p>
