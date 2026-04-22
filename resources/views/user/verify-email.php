<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * Interstitial form: confirms email verification only on POST so that single-use tokens are not silently consumed by
 * email link scanners (Outlook SafeLinks, antivirus prefetch) before the recipient clicks.
 *
 * @var \app\models\VerifyEmailForm $model Verify email form model.
 * @var string $token Verification token preserved across the post/redirect cycle.
 * @var \yii\web\View $this View component instance.
 */
$this->title = 'Confirm email';
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>Click the button below to confirm your email address.</p>
<?= Html::beginForm(['user/confirm-email', 'token' => $token], 'post') ?>
<p>
    <?= Html::submitButton('Confirm') ?>
</p>
<?= Html::endForm() ?>
