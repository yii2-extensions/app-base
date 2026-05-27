<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\SignupAction;
use Codeception\Test\Unit;
use Yii;
use yii\mail\{BaseMailer, MailEvent};

/**
 * Unit tests for {@see SignupAction} account registration and verification email dispatch.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class SignupActionTest extends Unit
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

    public function testActionSignupGet(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/signup';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new SignupAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'GET must render the form.',
        );
    }

    public function testActionSignupPostMailerFailure(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/signup';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'SignupForm' => [
                    'username' => 'unit_mailer_fail_user',
                    'email' => 'unit.mailer.fail@example.com',
                    'password' => 'password123',
                ],
            ],
        );

        $handler = static function (MailEvent $event): void {
            $event->isValid = false;
        };

        Yii::$app->mailer->on(BaseMailer::EVENT_BEFORE_SEND, $handler);

        try {
            $action = new SignupAction(new PhpViewRenderer(), Yii::$app->mailer);
            $response = $action->run();
        } finally {
            Yii::$app->mailer->off(BaseMailer::EVENT_BEFORE_SEND, $handler);
        }

        self::assertNotEmpty(
            $response,
            'Mailer veto must produce a redirect response.',
        );
    }

    public function testActionSignupPostSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/signup';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'SignupForm' => [
                    'username' => 'unit_test_user',
                    'email' => 'unit.test.user@example.com',
                    'password' => 'password123',
                ],
            ],
        );

        $action = new SignupAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Successful signup must produce a redirect response.',
        );
    }

    public function testActionSignupPostValidationErrors(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/signup';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'SignupForm' => [
                    'username' => '',
                    'email' => '',
                    'password' => '',
                ],
            ],
        );

        $action = new SignupAction(new PhpViewRenderer(), Yii::$app->mailer);

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Validation failure must produce a redirect response.',
        );
    }

    public function testActionSignupRedirectsAuthenticatedUserToHome(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/signup';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $user = User::findIdentity(1);

        self::assertNotNull(
            $user,
            "Fixture user with ID '1' must exist.",
        );

        Yii::$app->user->login($user);

        $result = Yii::$app->runAction('user/signup');

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
