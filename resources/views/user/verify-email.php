<?php

declare(strict_types=1);

use app\usecases\shared\user\VerifyEmailForm;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var VerifyEmailForm $model Verify email form model.
 * @var string $token Verification token preserved across the post/redirect cycle.
 * @var View $this View component instance.
 */
$this->title = 'Confirm email';
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>Click the button below to confirm your email address.</p>
<?= Html::beginForm(['/user/confirm-email', 'token' => $token], 'post') ?>
<p>
    <?= Html::submitButton('Confirm') ?>
</p>
<?= Html::endForm();
