<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\GuestOnlyActionBehaviors;
use app\usecases\shared\view\ViewRendererInterface;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Action, Response};

/**
 * Validates {@see ResendVerificationEmailForm} and re-sends the verification email through the configured
 * {@see MailerInterface}. Gated to anonymous visitors via {@see GuestOnlyActionBehaviors}.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ResendVerificationEmailAction extends Action
{
    use GuestOnlyActionBehaviors;

    /**
     * Creates a new instance with the renderer and mailer resolved from the DI container.
     *
     * @param ViewRendererInterface $view Renderer used to produce the resend-verification-email view.
     * @param MailerInterface $mailer Mailer used to dispatch the verification email.
     */
    public function __construct(
        private readonly ViewRendererInterface $view,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    /**
     * Handles the resend-verification-email request.
     *
     * @return Response|string Redirect to the home page once the request is accepted, a redirect back to the form on
     * validation failure, or the rendered resend view.
     */
    public function run(): Response|string
    {
        $model = new ResendVerificationEmailForm();

        /** @var array<string, mixed> $post */
        $post = Yii::$app->request->post();

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

            return Yii::$app->response->redirect(Yii::$app->homeUrl);
        }

        if (Yii::$app->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return Yii::$app->response->redirect(['/user/resend-verification-email']);
        }

        return $this->view->render(
            'user/resend-verification-email',
            ['model' => $model],
        );
    }
}
