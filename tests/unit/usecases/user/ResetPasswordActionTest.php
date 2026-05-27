<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\ResetPasswordAction;
use Codeception\Test\Unit;
use RuntimeException;
use Yii;
use yii\base\{Event, ModelEvent};
use yii\db\BaseActiveRecord;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for {@see ResetPasswordAction} token-based password update.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ResetPasswordActionTest extends Unit
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

    public function testActionResetPasswordGet(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $token = $this->fixtureToken();

        $action = new ResetPasswordAction(new PhpViewRenderer());

        $response = $action->run($token);

        self::assertNotEmpty(
            $response,
            'GET with valid token must render the form.',
        );
    }

    public function testActionResetPasswordPostSaveFailure(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token = $this->fixtureToken();

        Yii::$app->request->setBodyParams(
            [
                'ResetPasswordForm' => ['password' => 'newpassword123'],
            ],
        );

        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        try {
            $action = new ResetPasswordAction(new PhpViewRenderer());
            $response = $action->run($token);
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }

        self::assertNotEmpty(
            $response,
            'Save failure must produce a redirect response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('error'),
            'Error flash must be set on save failure.',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            'Success flash must be absent.',
        );
    }

    public function testActionResetPasswordPostSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token = $this->fixtureToken();

        Yii::$app->request->setBodyParams(
            [
                'ResetPasswordForm' => ['password' => 'newpassword123'],
            ],
        );

        $action = new ResetPasswordAction(new PhpViewRenderer());

        $response = $action->run($token);

        self::assertNotEmpty(
            $response,
            'Successful reset must produce a redirect response.',
        );
    }

    public function testActionResetPasswordPostThrowsDuringSave(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token = $this->fixtureToken();

        Yii::$app->request->setBodyParams(
            [
                'ResetPasswordForm' => ['password' => 'newpassword123'],
            ],
        );

        $handler = static function (): never {
            throw new RuntimeException('Simulated DB failure during password save.');
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        try {
            $action = new ResetPasswordAction(new PhpViewRenderer());
            $response = $action->run($token);
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }

        self::assertNotEmpty(
            $response,
            'Throwable must be caught and produce a response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('error'),
            'Error flash must be set on save exception.',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            'Success flash must be absent.',
        );
    }

    public function testActionResetPasswordPostValidationErrors(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $token = $this->fixtureToken();

        Yii::$app->request->setBodyParams(
            [
                'ResetPasswordForm' => ['password' => 'short'],
            ],
        );

        $action = new ResetPasswordAction(new PhpViewRenderer());

        $response = $action->run($token);

        self::assertNotEmpty(
            $response,
            'Validation failure must produce a redirect response.',
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

    public function testThrowBadRequestHttpExceptionWhenResetPasswordTokenIsInvalid(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/reset-password';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new ResetPasswordAction(new PhpViewRenderer());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Wrong password reset token.',
        );

        $action->run('invalid-token');
    }

    protected function tearDown(): void
    {
        Yii::$app->request->setBodyParams([]);
        Yii::$app->session->removeAllFlashes();

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }

    private function fixtureToken(): string
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' must exist.",
        );

        $token = $user->password_reset_token;

        self::assertNotNull(
            $token,
            'Fixture must carry a password reset token.',
        );

        return $token;
    }
}
