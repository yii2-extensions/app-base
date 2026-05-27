<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\shared\view\PhpViewRenderer;
use app\usecases\user\VerifyEmailAction;
use Codeception\Test\Unit;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for {@see VerifyEmailAction} interstitial rendering and token validation.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class VerifyEmailActionTest extends Unit
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

    public function testActionVerifyEmailRendersInterstitial(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/verify-email';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $user = User::findOne(['username' => 'test.test', 'status' => User::STATUS_INACTIVE]);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' must exist.",
        );

        $token = $user->verification_token;

        self::assertNotNull(
            $token,
            'Fixture must carry a verification token.',
        );

        $action = new VerifyEmailAction(new PhpViewRenderer());

        $response = $action->run($token);

        self::assertNotEmpty(
            $response,
            'Body must be non-empty.',
        );
        self::assertIsString(
            $response,
            'Body must render as a string.',
        );
        self::assertStringContainsString(
            'user/confirm-email',
            $response,
            'Form must POST to the confirm-email route.',
        );

        $reloaded = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $reloaded,
            "Fixture user 'test.test' must persist after 'GET'.",
        );
        self::assertSame(
            User::STATUS_INACTIVE,
            $reloaded->status,
            "'GET' must not consume the token or activate the user.",
        );
    }

    public function testThrowBadRequestHttpExceptionWhenVerifyEmailTokenIsInvalid(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/verify-email';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $action = new VerifyEmailAction(new PhpViewRenderer());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Wrong verify email token.',
        );

        $action->run('invalid-token');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
