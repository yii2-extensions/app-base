<?php

declare(strict_types=1);

use app\usecases\shared\user\User;
use yii\web\View;

/**
 * @var User $user User instance.
 * @var View $this View component instance.
 */
$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['user/verify-email', 'token' => $user->verification_token]);
?>
Hello <?= $user->username ?>,

Follow the link below to verify your email:

<?= $verifyLink;
