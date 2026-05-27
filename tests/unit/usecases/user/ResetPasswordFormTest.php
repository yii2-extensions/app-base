<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use app\usecases\shared\user\User;
use app\usecases\user\ResetPasswordForm;
use Codeception\Test\Unit;
use ReflectionProperty;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for {@see \app\usecases\user\ResetPasswordForm} model.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ResetPasswordFormTest extends Unit
{
    protected UnitTester|null $tester = null;

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

    public function testResetCorrectToken(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );
        self::assertNotNull(
            $user->password_reset_token,
            "Fixture user 'okirlin' has a 'password reset token'.",
        );

        $token = $user->password_reset_token;

        $form = new ResetPasswordForm($token);

        $form->password = 'new_password_123';

        self::assertTrue(
            $form->resetPassword(),
            'Password reset must succeed with a valid token and password.',
        );

        $user->refresh();

        self::assertNull(
            $user->password_reset_token,
            'Password reset token must be cleared after reset.',
        );
        self::assertTrue(
            $user->validatePassword('new_password_123'),
            'The new password must validate after reset.',
        );
    }

    public function testResetPasswordReturnsFalseWhenUserIsNull(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );
        self::assertNotNull(
            $user->password_reset_token,
            "Fixture user 'okirlin' has a 'password reset token'.",
        );

        $token = $user->password_reset_token;

        $form = new ResetPasswordForm($token);

        $reflection = new ReflectionProperty($form, 'user');

        $reflection->setValue($form, null);

        self::assertFalse(
            $form->resetPassword(),
            "Return 'false' when 'user' is 'null'.",
        );
    }

    public function testThrowInvalidArgumentExceptionWhenTokenIsEmptyOrInvalid(): void
    {
        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function (): void {
                new ResetPasswordForm('');
            },
        );
        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function (): void {
                new ResetPasswordForm('notexistingtoken_1391882543');
            },
        );
    }
}
