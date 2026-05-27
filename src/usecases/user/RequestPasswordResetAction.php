<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\GuestOnlyActionBehaviors;
use app\usecases\shared\view\ViewRendererInterface;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Action, Response};

/**
 * Validates {@see PasswordResetRequestForm} and dispatches the reset link through the configured
 * {@see MailerInterface}. Gated to anonymous visitors via {@see GuestOnlyActionBehaviors}.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class RequestPasswordResetAction extends Action
{
    use GuestOnlyActionBehaviors;

    /**
     * Creates a new instance with the renderer and mailer resolved from the DI container.
     *
     * @param ViewRendererInterface $view Renderer used to produce the request-password-reset view.
     * @param MailerInterface $mailer Mailer used to dispatch the reset link.
     */
    public function __construct(
        private readonly ViewRendererInterface $view,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    /**
     * Handles the password-reset request.
     *
     * @return Response|string Redirect to the home page once the request is accepted, a redirect back to the form on
     * validation failure, or the rendered request view.
     */
    public function run(): Response|string
    {
        $model = new PasswordResetRequestForm();

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
                'If an account with that email exists, instructions to reset the password have been sent.',
            );

            return Yii::$app->response->redirect(Yii::$app->homeUrl);
        }

        if (Yii::$app->request->isPost && $model->hasErrors()) {
            Yii::$app->session->setFlash('errors', $model->getErrors());

            return Yii::$app->response->redirect(['/user/request-password-reset']);
        }

        return $this->view->render(
            'user/request-password-reset',
            ['model' => $model],
        );
    }
}
