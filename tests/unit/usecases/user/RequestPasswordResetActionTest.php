<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\RequestPasswordResetAction;
use Codeception\Test\Unit;
use Yii;
use yii\mail\{BaseMailer, MailEvent};

/**
 * Unit tests for {@see RequestPasswordResetAction} reset-link dispatch and guest-only access guard.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class RequestPasswordResetActionTest extends Unit
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

    public function testActionRequestPasswordResetGet(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new RequestPasswordResetAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'GET must render the form.',
        );
    }

    public function testActionRequestPasswordResetPostInvalidEmailFormat(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'PasswordResetRequestForm' => ['email' => 'not-an-email'],
            ],
        );

        $action = new RequestPasswordResetAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Invalid format must produce a redirect response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('errors'),
            'Errors flash must capture form errors.',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            'Success flash must be absent.',
        );
    }

    public function testActionRequestPasswordResetPostMailerFailure(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'PasswordResetRequestForm' => ['email' => 'okirlin@example.com'],
            ],
        );

        $handler = static function (MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        try {
            $action = new RequestPasswordResetAction(new PhpViewRenderer(), Yii::$app->mailer);
            $response = $action->run();
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotEmpty(
            $response,
            'Mailer veto must still produce the generic success response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('success'),
            'Success flash must be set even on mailer failure (enumeration-safe).',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('errors'),
            'Errors flash must be absent (enumeration-safe).',
        );
    }

    public function testActionRequestPasswordResetPostSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'PasswordResetRequestForm' => ['email' => 'okirlin@example.com'],
            ],
        );

        $action = new RequestPasswordResetAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Successful send must produce a redirect response.',
        );
    }

    public function testActionRequestPasswordResetPostUnknownEmailReturnsGenericSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'PasswordResetRequestForm' => ['email' => 'nonexistent@example.com'],
            ],
        );

        $action = new RequestPasswordResetAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Unknown email must produce the generic success response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('success'),
            '`success` flash must be set (enumeration-safe).',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('errors'),
            '`errors` flash must be absent (enumeration-safe).',
        );
    }

    public function testActionRequestPasswordResetRedirectsAuthenticatedUserToHome(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/request-password-reset';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $user = User::findIdentity(1);

        self::assertNotNull(
            $user,
            "Fixture user with ID '1' must exist.",
        );

        Yii::$app->user->login($user);

        $result = Yii::$app->runAction('user/request-password-reset');

        self::assertNull(
            $result,
            'Guard must short-circuit before the action body runs.',
        );
        self::assertSame(
            302,
            Yii::$app->response->statusCode,
            "Status must be '302'.",
        );
        self::assertSame(
            'http://localhost/',
            Yii::$app->response->headers->get('Location'),
            'Location must point to the home URL.',
        );
        self::assertFalse(
            Yii::$app->user->isGuest,
            'Guard must not log the user out.',
        );
    }

    protected function tearDown(): void
    {
        Yii::$app->request->setBodyParams([]);
        Yii::$app->session->removeAllFlashes();
        Yii::$app->user->logout(false);
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->headers->remove('Location');

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
