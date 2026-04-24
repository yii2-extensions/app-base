<?php

declare(strict_types=1);

namespace app\tests\unit;

use app\controllers\SiteController;
use app\tests\support\fixtures\UserFixture;
use RuntimeException;
use Yii;
use yii\mail\{BaseMailer, MailEvent};
use yii\web\HttpException;

/**
 * Unit tests for {@see SiteController} all actions.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class SiteControllerTest extends \Codeception\Test\Unit
{
    /**
     * @return array{user: array{class: string, dataFile: string}}
     */
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                // @phpstan-ignore binaryOp.invalid
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testActionAbout(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/about';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        $response = $controller->actionAbout();

        self::assertNotEmpty(
            $response,
            "Expected 'actionAbout' to return an instance of Response.",
        );
    }

    public function testActionContactGet(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        $response = $controller->actionContact();

        self::assertNotEmpty(
            $response,
            "Expected 'actionContact' to return an instance of Response for 'GET' request.",
        );
    }

    public function testActionContactPostMailerFailure(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'ContactForm' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'phone' => '(555) 123-4567',
                    'subject' => 'Test Subject',
                    'body' => 'Test body content.',
                    'turnstileToken' => 'test-token',
                ],
            ],
        );

        $handler = static function (MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        try {
            $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

            Yii::$app->controller = $controller;

            $response = $controller->actionContact();
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotEmpty(
            $response,
            "Expected 'actionContact' to redirect with error flash when mailer fails.",
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('error'),
            "Expected 'error' flash to be set when mailer fails without validation errors.",
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            "Expected 'success' flash NOT to be set when mailer fails.",
        );
    }

    public function testActionContactPostMailerThrows(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'ContactForm' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'phone' => '(555) 123-4567',
                    'subject' => 'Test Subject',
                    'body' => 'Test body content.',
                    'turnstileToken' => 'test-token',
                ],
            ],
        );

        $handler = static function (): void {
            throw new RuntimeException('Simulated mailer transport exception.');
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        try {
            $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

            Yii::$app->controller = $controller;

            $response = $controller->actionContact();
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotEmpty(
            $response,
            "Expected 'actionContact' to return Response when mailer throws instead of propagating exception.",
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('error'),
            "Expected 'error' flash to be set when the mailer throws and the exception is caught.",
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            "Expected 'success' flash NOT to be set when the mailer throws.",
        );
    }

    public function testActionContactPostSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'ContactForm' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'phone' => '(555) 123-4567',
                    'subject' => 'Test Subject',
                    'body' => 'Test body content.',
                    'turnstileToken' => 'test-token',
                ],
            ],
        );

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        $response = $controller->actionContact();

        self::assertNotEmpty(
            $response,
            "Expected 'actionContact' to redirect with success flash on successful email send.",
        );
    }

    public function testActionContactPostValidationErrors(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/contact';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'ContactForm' => [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'subject' => '',
                    'body' => '',
                    'turnstileToken' => '',
                ],
            ],
        );

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        $response = $controller->actionContact();

        self::assertNotEmpty(
            $response,
            "Expected 'actionContact' to redirect with errors flash on validation failure.",
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('errors'),
            "Expected 'errors' flash to be set when the contact form fails validation.",
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            "Expected 'success' flash NOT to be set when validation fails.",
        );
    }

    public function testActionErrorHidesNonUserExceptionMessage(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        Yii::$app->errorHandler->exception = new RuntimeException('Database connection lost');

        $response = $controller->runAction('error');

        self::assertIsString(
            $response,
            'Error view should render as a string.',
        );
        self::assertStringContainsString(
            'Error 500',
            $response,
            "Heading should show status '500'.",
        );
        self::assertStringContainsString(
            'An internal server error occurred.',
            $response,
            'Generic fallback message should be rendered.',
        );
        self::assertStringNotContainsString(
            'Database connection lost',
            $response,
            'Raw Throwable message must not leak to the client.',
        );
        self::assertSame(
            500,
            Yii::$app->response->statusCode,
            "HTTP status must default to '500'.",
        );
    }

    public function testActionErrorShowsHttpExceptionMessage(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        Yii::$app->errorHandler->exception = new HttpException(404, 'Page not found');

        $response = $controller->runAction('error');

        self::assertIsString(
            $response,
            'Error view should render as a string.',
        );
        self::assertStringContainsString(
            'Error 404',
            $response,
            "Heading should show the HttpException status '404'.",
        );
        self::assertStringContainsString(
            'Page not found',
            $response,
            'HttpException message should render verbatim.',
        );
        self::assertStringNotContainsString(
            'An internal server error occurred.',
            $response,
            'Generic fallback must not appear when a user-safe message exists.',
        );
        self::assertSame(
            404,
            Yii::$app->response->statusCode,
            'HTTP status must match the HttpException code.',
        );
    }

    public function testActionErrorSynthesizesNotFoundWhenExceptionIsNull(): void
    {
        $_SERVER['REQUEST_URI'] = '/site/error';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        Yii::$app->errorHandler->exception = null;

        $response = $controller->runAction('error');

        self::assertIsString(
            $response,
            'Error view should render as a string.',
        );
        self::assertStringContainsString(
            'Error 404',
            $response,
            "Heading should show synthesized '404' status.",
        );
        self::assertStringContainsString(
            'Page not found',
            $response,
            'Synthesized NotFoundHttpException message should render.',
        );
        self::assertSame(
            404,
            Yii::$app->response->statusCode,
            "HTTP status must be '404'.",
        );
    }

    public function testActionIndex(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);

        Yii::$app->controller = $controller;

        $response = $controller->actionIndex();

        self::assertNotEmpty(
            $response,
            "Expected 'actionIndex' to return an instance of Response.",
        );
    }

    protected function tearDown(): void
    {
        Yii::$app->request->setBodyParams([]);

        Yii::$app->controller = null;
        Yii::$app->errorHandler->exception = null;

        Yii::$app->session->removeAllFlashes();

        unset(
            $_SERVER['REQUEST_URI'],
            $_SERVER['SERVER_NAME'],
            $_SERVER['REQUEST_METHOD'],
        );

        parent::tearDown();
    }
}
