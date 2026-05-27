<?php

declare(strict_types=1);

use app\usecases\user\PasswordResetRequestForm;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var PasswordResetRequestForm $model Password reset request model.
 * @var View $this View component instance.
 */
$this->title = 'Request password reset';
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
