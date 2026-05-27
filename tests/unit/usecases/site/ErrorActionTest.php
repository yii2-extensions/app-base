<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\site;

use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\site\ErrorAction;
use Codeception\Test\Unit;
use RuntimeException;
use Yii;
use yii\web\HttpException;

/**
 * Unit tests for {@see ErrorAction} exception rendering, status mapping, and message filtering.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ErrorActionTest extends Unit
{
    public function testActionErrorHidesNonUserExceptionMessage(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        Yii::$app->errorHandler->exception = new RuntimeException('Database connection lost');

        $action = new ErrorAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertIsString(
            $response,
            'Body must render as a string.',
        );
        self::assertStringContainsString(
            'Error 500',
            $response,
            "Heading must show '500'.",
        );
        self::assertStringContainsString(
            'An internal server error occurred.',
            $response,
            'Generic fallback message must be rendered.',
        );
        self::assertStringNotContainsString(
            'Database connection lost',
            $response,
            'Raw Throwable message must not leak to the client.',
        );
        self::assertSame(
            500,
            Yii::$app->response->statusCode,
            "Status must default to '500'."
        );
    }

    public function testActionErrorReturnsPlainTextForAjaxRequest(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        Yii::$app->errorHandler->exception = new HttpException(404, 'Page not found');

        $action = new ErrorAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertIsString(
            $response,
            'AJAX body must be a plain string.',
        );
        self::assertStringContainsString(
            'Page not found',
            $response,
            'AJAX body must include the message.',
        );
        self::assertStringNotContainsString(
            '<!DOCTYPE html>',
            $response,
            'AJAX body must bypass the layout.',
        );
        self::assertSame(
            404,
            Yii::$app->response->statusCode,
            'Status must match the HttpException code.',
        );
    }

    public function testActionErrorShowsHttpExceptionMessage(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        Yii::$app->errorHandler->exception = new HttpException(404, 'Page not found');

        $action = new ErrorAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertIsString(
            $response,
            'Body must render as a string.',
        );
        self::assertStringContainsString(
            'Error 404',
            $response,
            "Heading must show '404'."
        );
        self::assertStringContainsString(
            'Page not found',
            $response,
            'HttpException message must render verbatim.'
        );
        self::assertStringNotContainsString(
            'An internal server error occurred.',
            $response,
            'Generic fallback must not appear when a user-safe message exists.',
        );
        self::assertSame(
            404,
            Yii::$app->response->statusCode,
            'Status must match the HttpException code.',
        );
    }

    public function testActionErrorSynthesizesNotFoundWhenExceptionIsNull(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        Yii::$app->errorHandler->exception = null;

        $action = new ErrorAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertIsString(
            $response,
            'Body must render as a string.',
        );
        self::assertStringContainsString(
            'Error 404',
            $response,
            "Synthesized status must be '404'.",
        );
        self::assertStringContainsString(
            'Page not found',
            $response,
            'Synthesized NotFoundHttpException message must render.',
        );
        self::assertSame(
            404,
            Yii::$app->response->statusCode,
            "Status must be '404'.",
        );
    }

    protected function tearDown(): void
    {
        Yii::$app->errorHandler->exception = null;
        Yii::$app->response->statusCode = 200;

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['HTTP_X_REQUESTED_WITH']);

        parent::tearDown();
    }
}
