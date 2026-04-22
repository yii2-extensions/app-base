<?php

declare(strict_types=1);

namespace app\controllers\Base;

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
use yii\data\ActiveDataProvider;
use yii\filters\{AccessControl, VerbFilter};
use yii\mail\MailerInterface;
use yii\web\{BadRequestHttpException, Controller, Response};

/**
 * Provides user-related actions (login, signup, password recovery, email verification, listing) with rendering
 * delegated to subclasses.
 *
 * Subclasses implement the `render*()` methods to plug in a presentation layer (PHP views, Inertia, JSON, etc.) while
 * inheriting all business logic, access rules, and HTTP verb constraints for free.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
abstract class AbstractUserController extends Controller
{
    public function __construct($id, $module, protected readonly MailerInterface $mailer, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders the user list view.
     *
     * @param ActiveDataProvider $dataProvider Paginated user data set.
     * @param UserSearch $searchModel Filter model carrying current query parameters.
     */
    abstract protected function renderIndex(
        ActiveDataProvider $dataProvider,
        UserSearch $searchModel,
    ): Response|string;

    /**
     * Renders the login form.
     *
     * @param LoginForm $model Bound login form (may carry validation errors on re-render).
     */
    abstract protected function renderLogin(LoginForm $model): Response|string;

    /**
     * Renders the password reset request form.
     *
     * @param PasswordResetRequestForm $model Bound request form (may carry validation errors on re-render).
     */
    abstract protected function renderRequestPasswordReset(PasswordResetRequestForm $model): Response|string;

    /**
     * Renders the resend verification email form.
     *
     * @param ResendVerificationEmailForm $model Bound resend form (may carry validation errors on re-render).
     */
    abstract protected function renderResendVerificationEmail(ResendVerificationEmailForm $model): Response|string;

    /**
     * Renders the reset password form.
     *
     * @param ResetPasswordForm $model Bound reset form (may carry validation errors on re-render).
     * @param string $token Single-use token from the password reset email.
     */
    abstract protected function renderResetPassword(ResetPasswordForm $model, string $token): Response|string;

    /**
     * Renders the signup form.
     *
     * @param SignupForm $model Bound signup form (may carry validation errors on re-render).
     */
    abstract protected function renderSignup(SignupForm $model): Response|string;

    /**
     * Renders the email verification interstitial form.
     *
     * @param VerifyEmailForm $model Token-bound verification form.
     * @param string $token Single-use verification token to forward to the POST handler.
     */
    abstract protected function renderVerifyEmail(VerifyEmailForm $model, string $token): Response|string;

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

        return $this->renderIndex($dataProvider, $searchModel);
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

        return $this->renderLogin($model);
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

        return $this->renderRequestPasswordReset($model);
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

        return $this->renderResendVerificationEmail($model);
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

        return $this->renderResetPassword($model, $token);
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

        return $this->renderSignup($model);
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

        return $this->renderVerifyEmail($model, $token);
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
