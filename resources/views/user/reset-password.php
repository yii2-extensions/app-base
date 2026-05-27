<?php

declare(strict_types=1);

use app\usecases\user\ResetPasswordForm;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var ResetPasswordForm $model Reset password form model.
 * @var string $token Password reset token preserved across the post/redirect cycle.
 * @var View $this View component instance.
 */
$this->title = 'Reset password';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm(['/user/reset-password', 'token' => $token], 'post') ?>
<p>
    <?= Html::activeLabel($model, 'password', ['label' => 'New password']) ?>
    <?= Html::activePasswordInput($model, 'password') ?>
</p>
<p>
    <?= Html::submitButton('Save') ?>
</p>
<?= Html::endForm();
