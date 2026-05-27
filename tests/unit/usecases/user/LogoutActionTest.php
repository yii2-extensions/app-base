<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\user\LogoutAction;
use Codeception\Test\Unit;
use Yii;

/**
 * Unit tests for {@see LogoutAction} session termination.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class LogoutActionTest extends Unit
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

    public function testActionLogout(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/logout';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $user = User::findIdentity(1);

        self::assertNotNull(
            $user,
            "Fixture user with ID '1' must exist.",
        );

        Yii::$app->user->login($user);

        self::assertFalse(
            Yii::$app->user->isGuest,
            'User must be authenticated before logout.',
        );

        $action = new LogoutAction();

        $response = $action->run();

        self::assertNotEmpty(
            $response,
            'Body must be a redirect response.',
        );
        self::assertTrue(
            Yii::$app->user->isGuest,
            'User must be a guest after logout.',
        );
    }

    protected function tearDown(): void
    {
        Yii::$app->user->logout(false);

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
