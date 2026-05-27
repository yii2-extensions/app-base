<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\LoginAction;
use Codeception\Test\Unit;
use Yii;
use yii\web\Response;

/**
 * Unit tests for {@see LoginAction} authentication and guest-only access guard.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class LoginActionTest extends Unit
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

    public function testActionLoginGet(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new LoginAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'GET must render the form.',
        );
    }

    public function testActionLoginPostSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'LoginForm' => [
                    'username' => 'admin',
                    'password' => 'password_0',
                ],
            ],
        );

        $action = new LoginAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Successful login must produce a redirect response.',
        );
    }

    public function testActionLoginPostSuccessHonorsReturnUrl(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'LoginForm' => [
                    'username' => 'admin',
                    'password' => 'password_0',
                ],
            ],
        );

        Yii::$app->user->setReturnUrl('/user/index');

        $action = new LoginAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertInstanceOf(
            Response::class,
            $response,
            'Successful login must return a redirect Response.',
        );
        self::assertStringEndsWith(
            '/user/index',
            (string) $response->headers->get('Location'),
            'Location must point to the saved `returnUrl`.',
        );
    }

    public function testActionLoginPostValidationErrors(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Yii::$app->request->setBodyParams(
            [
                'LoginForm' => [
                    'username' => '',
                    'password' => '',
                ],
            ],
        );

        $action = new LoginAction(new PhpViewRenderer());

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Validation failure must produce a redirect response.',
        );
    }

    public function testActionLoginRedirectsAuthenticatedUserToHome(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $user = User::findIdentity(1);

        self::assertNotNull($user, "Fixture user with ID '1' must exist.");

        Yii::$app->user->login($user);

        $result = Yii::$app->runAction('user/login');

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
