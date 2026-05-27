<?php

declare(strict_types=1);

use app\usecases\user\ResendVerificationEmailForm;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var ResendVerificationEmailForm $model Resend verification email model.
 * @var View $this View component instance.
 */
$this->title = 'Resend verification email';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm('', 'post') ?>
<p>
    <?= Html::activeLabel($model, 'email', ['label' => 'Email']) ?>
    <?= Html::activeInput('email', $model, 'email') ?>
</p>
<p>
    <?= Html::submitButton('Send') ?>
</p>
<?= Html::endForm();
