<?php

declare(strict_types=1);

namespace app\usecases\site;

use app\usecases\shared\filters\CsrfValidationFilter;
use app\usecases\shared\view\ViewRendererInterface;
use Throwable;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Action, Response};

/**
 * Handles the contact form: validates input, dispatches the message through the configured {@see MailerInterface},
 * stores user feedback as flash messages, and renders or redirects via {@see ViewRendererInterface}.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ContactAction extends Action
{
    public function __construct(
        private readonly ViewRendererInterface $view,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    /**
     * Validates the CSRF token on `POST`.
     */
    public function behaviors(): array
    {
        return [
            'csrf' => CsrfValidationFilter::class,
        ];
    }

    public function run(): Response|string
    {
        $model = new ContactForm();

        /** @var array<string, mixed> $post */
        $post = Yii::$app->request->post();

        if ($model->load($post)) {
            $params = Yii::$app->params;

            try {
                $sent = $model->contact(
                    $this->mailer,
                    $params['adminEmail'],
                    $params['senderEmail'],
                    $params['senderName'],
                );
            } catch (Throwable $e) {
                Yii::error(
                    $e->getMessage(),
                    __METHOD__,
                );

                $sent = false;
            }

            if ($sent) {
                Yii::$app->session->setFlash(
                    'success',
                    'Thank you for contacting us. We will respond to you as soon as possible.',
                );

                return Yii::$app->response->redirect(['/site/contact']);
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash('errors', $model->getErrors());
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to send your message at this time.',
                );
            }

            return Yii::$app->response->redirect(['/site/contact']);
        }

        return $this->view->render(
            'site/contact',
            ['model' => $model],
        );
    }
}
