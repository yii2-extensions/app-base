<?php

declare(strict_types=1);

use app\usecases\shared\user\User;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var User $user User instance.
 * @var View $this View component instance.
 */
$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['user/verify-email', 'token' => $user->verification_token]);
?>
<div class="verify-email">
    <p>Hello <?= Html::encode($user->username) ?>,</p>

    <p>Follow the link below to verify your email:</p>

    <p><?= Html::a(Html::encode($verifyLink), $verifyLink) ?></p>
</div>
