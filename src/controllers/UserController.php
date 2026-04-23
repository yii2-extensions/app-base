<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\{
    LoginForm,
    PasswordResetRequestForm,
    ResendVerificationEmailForm,
    ResetPasswordForm,
    SignupForm,
    UserSearch,
    VerifyEmailForm,
};
use Throwable;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\{AccessControl, VerbFilter};
use yii\mail\MailerInterface;
use yii\web\{BadRequestHttpException, Controller, Response};

/**
 * Provides user-related actions (login, signup, password recovery, email verification, listing) rendered through the
 * default PHP view layer.
 *
 * Frontend overlays with a different presentation strategy (Inertia, JSON, API) may extend this class and override
 * individual action methods to return their own response type while inheriting access rules and HTTP verb constraints.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
class UserController extends Controller
{
    public function __construct($id, $module, protected readonly MailerInterface $mailer, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Confirms the email verification submitted from the interstitial form.
     *
     * Performing the actual verification only on POST prevents email link scanners (Outlook SafeLinks, antivirus,
     * browser prefetch) from silently consuming the single-use token before the recipient clicks.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response Redirect to the home page with a flash message.
     */
    public function actionConfirmEmail(string $token): Response
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->verifyEmail() !== null) {
            Yii::$app->session->setFlash(
                'success',
                'Your email has been confirmed!',
            );

            return $this->goHome();
        }

        Yii::$app->session->setFlash(
            'error',
            'Sorry, we are unable to verify your account with provided token.',
        );

        return $this->goHome();
    }

    /**
     * Displays user list.
     *
     * @return Response|string Rendered user list view.
     */
    public function actionIndex(): Response|string
    {
        $searchModel = new UserSearch();

        /** @var array<string, mixed> $queryParams */
        $queryParams = Yii::$app->request->queryParams;

        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', ['dataProvider' => $dataProvider, 'searchModel' => $searchModel]);
    }

    /**
     * Login action.
     *
     * @return Response|string Redirect after success, or the rendered login view.
     */
    public function actionLogin(): Response|string
    {
        $model = new LoginForm();

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        if ($model->load($post) && $model->login()) {
            return $this->goHome();
        }

        if ($this->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return $this->redirect(['user/login']);
        }

        return $this->render('login', ['model' => $model]);
    }

    /**
     * Logout action.
     *
     * @return Response Redirect to the home page.
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Requests password reset.
     *
     * @return Response|string Redirect after submission, or the rendered request view.
     */
    public function actionRequestPasswordReset(): Response|string
    {
        $model = new PasswordResetRequestForm();

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        $params = Yii::$app->params;

        if ($model->load($post) && $model->validate()) {
            $model->sendEmail(
                $this->mailer,
                $params['supportEmail'],
                Yii::$app->name,
            );

            Yii::$app->session->setFlash(
                'success',
                'If an account with that email exists, instructions to reset the password have been sent.',
            );

            return $this->goHome();
        }

        if ($this->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return $this->redirect(['user/request-password-reset']);
        }

        return $this->render('request-password-reset', ['model' => $model]);
    }

    /**
     * Resends verification email.
     *
     * @return Response|string Redirect after submission, or the rendered resend view.
     */
    public function actionResendVerificationEmail(): Response|string
    {
        $model = new ResendVerificationEmailForm();

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        $params = Yii::$app->params;

        if ($model->load($post) && $model->validate()) {
            $model->sendEmail(
                $this->mailer,
                $params['supportEmail'],
                Yii::$app->name,
            );

            Yii::$app->session->setFlash(
                'success',
                'If an account with that email exists, a verification email has been sent.',
            );

            return $this->goHome();
        }

        if ($this->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return $this->redirect(['user/resend-verification-email']);
        }

        return $this->render('resend-verification-email', ['model' => $model]);
    }

    /**
     * Resets password.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response|string Redirect after success, or the rendered reset view.
     */
    public function actionResetPassword(string $token): Response|string
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        if ($model->load($post)) {
            try {
                $saved = $model->validate() && $model->resetPassword();
            } catch (Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                $saved = false;
            }

            if ($saved) {
                Yii::$app->session->setFlash(
                    'success',
                    'New password saved.',
                );

                return $this->goHome();
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash('errors', $model->getErrors());
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to save your new password at this time.',
                );
            }

            return $this->redirect(['user/reset-password', 'token' => $token]);
        }

        return $this->render('reset-password', ['model' => $model, 'token' => $token]);
    }

    /**
     * Signs user up.
     *
     * @return Response|string Redirect after submission, or the rendered signup view.
     */
    public function actionSignup(): Response|string
    {
        $model = new SignupForm();

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        if ($model->load($post)) {
            $params = Yii::$app->params;

            $signed = $model->signup(
                $this->mailer,
                $params['supportEmail'],
                Yii::$app->name,
            );

            if ($signed === true) {
                Yii::$app->session->setFlash(
                    'success',
                    'Thank you for registration. Please check your inbox for verification email.',
                );

                return $this->goHome();
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash('errors', $model->getErrors());
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to complete your registration at this time.',
                );
            }

            return $this->redirect(['user/signup']);
        }

        return $this->render('signup', ['model' => $model]);
    }

    /**
     * Renders the email verification interstitial form.
     *
     * The actual verification is performed by {@see actionConfirmEmail()} on POST so that single-use tokens are not
     * silently consumed by email link scanners (Outlook SafeLinks, antivirus, browser prefetch) before the recipient
     * clicks.
     *
     * @throws BadRequestHttpException if the token is invalid.
     *
     * @return Response|string Rendered confirmation form.
     */
    public function actionVerifyEmail(string $token): Response|string
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->render('verify-email', ['model' => $model, 'token' => $token]);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'confirm-email',
                    'index',
                    'login',
                    'logout',
                    'request-password-reset',
                    'resend-verification-email',
                    'reset-password',
                    'signup',
                    'verify-email',
                ],
                'rules' => [
                    [
                        'actions' => [
                            'login',
                            'request-password-reset',
                            'resend-verification-email',
                            'signup',
                        ],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['confirm-email', 'reset-password', 'verify-email'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'confirm-email' => ['post'],
                    'index' => ['get'],
                    'logout' => ['post'],
                    'verify-email' => ['get'],
                ],
            ],
        ];
    }
}
