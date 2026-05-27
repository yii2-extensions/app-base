<?php

declare(strict_types=1);

namespace app\usecases\site;

use app\usecases\shared\view\ViewRendererInterface;
use Throwable;
use Yii;
use yii\base\{Exception, UserException};
use yii\web\{Action, HttpException, NotFoundHttpException, Response};

/**
 * Renders captured exceptions through the frontend overlay's {@see ViewRendererInterface}.
 *
 * Replaces {@see \yii\web\ErrorAction}, which requires a hosting controller and is therefore not standalone-compatible.
 *
 * Synthesizes a {@see NotFoundHttpException} when the error handler captured nothing, sets the HTTP status code from
 * the exception, exposes only {@see UserException} messages to the user (other throwables fall back to a generic
 * message to avoid leaking internals), and returns a plain-text body for AJAX requests.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ErrorAction extends Action
{
    /**
     * Creates a new instance with the renderer supplied by the active frontend overlay.
     *
     * @param ViewRendererInterface $view Renderer used to produce the error page.
     */
    public function __construct(private readonly ViewRendererInterface $view)
    {
        parent::__construct();
    }

    /**
     * Renders the captured exception and applies the matching `HTTP` status code.
     *
     * @return Response|string Plain-text summary for `AJAX` requests, or the rendered error view otherwise.
     */
    public function run(): Response|string
    {
        $exception = Yii::$app->errorHandler->exception ?? new NotFoundHttpException(Yii::t('yii', 'Page not found.'));

        Yii::$app->response->setStatusCodeByException($exception);

        $name = $this->resolveName($exception);
        $message = $this->resolveMessage($exception);

        if (Yii::$app->request->getIsAjax()) {
            return "{$name}: {$message}";
        }

        return $this->view->render(
            'site/error',
            [
                'exception' => $exception,
                'message' => $message,
                'name' => $name,
            ],
        );
    }

    /**
     * Returns the user-facing message for the exception.
     *
     * Exposes the message verbatim only for {@see UserException}; any other throwable yields a generic message so
     * internal details never reach the client.
     *
     * @param Throwable $exception Exception captured by the error handler.
     *
     * @return string Message safe to display to the user.
     */
    private function resolveMessage(Throwable $exception): string
    {
        return $exception instanceof UserException
            ? $exception->getMessage()
            : Yii::t('yii', 'An internal server error occurred.');
    }

    /**
     * Returns the exception name, suffixed with its code when non-zero.
     *
     * Uses {@see Exception::getName()} for framework exceptions and a generic label otherwise; appends the `HTTP`
     * status code for {@see HttpException}, or the exception code for any other throwable.
     *
     * @param Throwable $exception Exception captured by the error handler.
     *
     * @return string Display name with the code appended when present.
     */
    private function resolveName(Throwable $exception): string
    {
        $name = $exception instanceof Exception ? $exception->getName() : Yii::t('yii', 'Error');
        $code = $exception instanceof HttpException ? $exception->statusCode : $exception->getCode();

        return $code !== 0 ? "{$name} (#{$code})" : $name;
    }
}
