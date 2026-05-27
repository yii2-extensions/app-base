<?php

declare(strict_types=1);

namespace app\usecases\user;

use app\usecases\shared\user\GuestOnlyActionBehaviors;
use app\usecases\shared\view\ViewRendererInterface;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Action, Response};

/**
 * Registers a new account through {@see SignupForm} and dispatches the verification email through the configured
 * {@see MailerInterface}.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class SignupAction extends Action
{
    use GuestOnlyActionBehaviors;

    /**
     * Creates a new instance with the renderer and mailer resolved from the DI container.
     *
     * @param ViewRendererInterface $view Renderer used to produce the signup view.
     * @param MailerInterface $mailer Mailer used to dispatch the verification email.
     */
    public function __construct(
        private readonly ViewRendererInterface $view,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    /**
     * Handles the signup request.
     *
     * @return Response|string Redirect to the home page on success, a redirect back to the signup route on failure, or
     * the rendered signup view.
     */
    public function run(): Response|string
    {
        $model = new SignupForm();

        /** @var array<string, mixed> $post */
        $post = Yii::$app->request->post();

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

                return Yii::$app->response->redirect(Yii::$app->homeUrl);
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash(
                    'errors',
                    $model->getErrors(),
                );
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to complete your registration at this time.',
                );
            }

            return Yii::$app->response->redirect(['/user/signup']);
        }

        return $this->view->render(
            'user/signup',
            ['model' => $model],
        );
    }
}
