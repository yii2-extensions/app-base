<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var \app\models\PasswordResetRequestForm $model Password reset request model.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Request password reset';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm('', 'post') ?>
<p>
    <?= Html::label('Email', 'passwordresetrequestform-email') ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::submitButton('Send') ?>
</p>
<?= Html::endForm() ?>
