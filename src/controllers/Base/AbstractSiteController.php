<?php

declare(strict_types=1);

namespace app\controllers\Base;

use app\models\ContactForm;
use Throwable;
use Yii;
use yii\mail\MailerInterface;
use yii\web\{Controller, HttpException, Response};

/**
 * Provides site page actions (home, about, contact, error) with rendering delegated to subclasses.
 *
 * Subclasses implement the `render*()` methods to plug in a presentation layer (PHP views, Inertia, JSON, etc.) while
 * inheriting all business logic for free.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
abstract class AbstractSiteController extends Controller
{
    public function __construct($id, $module, protected readonly MailerInterface $mailer, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Renders the about page.
     */
    abstract protected function renderAbout(): Response|string;

    /**
     * Renders the contact page form.
     *
     * @param ContactForm $model Bound contact form (may carry validation errors on re-render).
     */
    abstract protected function renderContact(ContactForm $model): Response|string;

    /**
     * Renders the error page.
     *
     * @param int $status HTTP status code (`500` for generic server errors).
     * @param string $message User-facing error message (sanitized by the caller when `YII_DEBUG` is `false`).
     */
    abstract protected function renderError(int $status, string $message): Response|string;

    /**
     * Renders the homepage.
     */
    abstract protected function renderIndex(): Response|string;

    /**
     * Displays about page.
     *
     * @return Response|string Rendered about view.
     */
    public function actionAbout(): Response|string
    {
        return $this->renderAbout();
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

        return $this->renderContact($model);
    }

    /**
     * Displays error page.
     *
     * @return Response|string Rendered error view.
     */
    public function actionError(): Response|string
    {
        $exception = Yii::$app->errorHandler->exception;

        $statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;
        $message = (YII_DEBUG && $exception instanceof Throwable)
            ? $exception->getMessage()
            : 'An internal server error occurred.';

        return $this->renderError($statusCode, $message);
    }

    /**
     * Displays homepage.
     *
     * @return Response|string Rendered homepage view.
     */
    public function actionIndex(): Response|string
    {
        return $this->renderIndex();
    }
}
