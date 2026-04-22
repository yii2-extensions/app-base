<?php

declare(strict_types=1);

namespace app\controllers;

use app\controllers\Base\AbstractUserController;
use app\models\{
    LoginForm,
    PasswordResetRequestForm,
    ResendVerificationEmailForm,
    ResetPasswordForm,
    SignupForm,
    UserSearch,
    VerifyEmailForm,
};
use yii\data\ActiveDataProvider;

/**
 * Default PHP-view implementation of {@see AbstractUserController}.
 *
 * Frontend overlays may replace this file via the `yii2-extensions/scaffold` plugin to render with a different
 * presentation strategy (Inertia, JSON, etc.).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class UserController extends AbstractUserController
{
    protected function renderIndex(ActiveDataProvider $dataProvider, UserSearch $searchModel): string
    {
        return $this->render('index', ['dataProvider' => $dataProvider, 'searchModel' => $searchModel]);
    }

    protected function renderLogin(LoginForm $model): string
    {
        return $this->render('login', ['model' => $model]);
    }

    protected function renderRequestPasswordReset(PasswordResetRequestForm $model): string
    {
        return $this->render('request-password-reset', ['model' => $model]);
    }

    protected function renderResendVerificationEmail(ResendVerificationEmailForm $model): string
    {
        return $this->render('resend-verification-email', ['model' => $model]);
    }

    protected function renderResetPassword(ResetPasswordForm $model, string $token): string
    {
        return $this->render('reset-password', ['model' => $model, 'token' => $token]);
    }

    protected function renderSignup(SignupForm $model): string
    {
        return $this->render('signup', ['model' => $model]);
    }

    protected function renderVerifyEmail(VerifyEmailForm $model, string $token): string
    {
        return $this->render('verify-email', ['model' => $model, 'token' => $token]);
    }
}
