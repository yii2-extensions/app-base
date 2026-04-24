<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\ContactForm;
use Throwable;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Controller, ErrorAction, Response};

/**
 * Provides site page actions (home, about, contact, error) rendered through the default PHP view layer.
 *
 * Frontend overlays with a different presentation strategy (Inertia, JSON, API) may extend this class and override
 * individual action methods to return their own response type.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
class SiteController extends Controller
{
    public function __construct($id, $module, protected readonly MailerInterface $mailer, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Displays about page.
     *
     * @return Response|string Rendered about view.
     */
    public function actionAbout(): Response|string
    {
        return $this->render('about');
    }

    /**
     * Displays contact page.
     *
     * @return Response|string Redirect after submission, or the rendered contact view.
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        /** @var array<string, mixed> $post */
        $post = $this->request->post();

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
                Yii::error($e->getMessage(), __METHOD__);
                $sent = false;
            }

            if ($sent) {
                Yii::$app->session->setFlash(
                    'success',
                    'Thank you for contacting us. We will respond to you as soon as possible.',
                );

                return $this->redirect(['site/contact']);
            }

            if ($model->hasErrors()) {
                Yii::$app->session->setFlash('errors', $model->getErrors());
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to send your message at this time.',
                );
            }

            return $this->redirect(['site/contact']);
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays homepage.
     *
     * @return Response|string Rendered homepage view.
     */
    public function actionIndex(): Response|string
    {
        return $this->render('index');
    }

    /**
     * Declares the error sub-action handled by {@see ErrorAction}, which sets the real HTTP status code from the
     * captured exception and renders only UserException messages (HttpException and subclasses); other {@see Throwable}
     * fall back to the generic message to avoid leaking internals.
     *
     * @return array<string, array{class: class-string}|class-string>
     */
    public function actions(): array
    {
        return [
            'error' => ['class' => ErrorAction::class],
        ];
    }
}
