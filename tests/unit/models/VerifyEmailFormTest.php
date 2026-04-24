<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\{User, VerifyEmailForm};
use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use ReflectionProperty;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for {@see \app\models\VerifyEmailForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class VerifyEmailFormTest extends \Codeception\Test\Unit
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
                // @phpstan-ignore-next-line
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testThrowInvalidArgumentExceptionWhenTokenBelongsToActiveUser(): void
    {
        $user = User::findOne(['username' => 'test2.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test2.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            "Fixture user 'test2.test' has a verification token.",
        );

        $token = $user->verification_token;

        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function () use ($token): void {
                new VerifyEmailForm($token);
            },
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
                new VerifyEmailForm('');
            },
        );
        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function (): void {
                new VerifyEmailForm('notexistingtoken_1391882543');
            },
        );
    }

    public function testVerifyCorrectToken(): void
    {
        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            "Fixture user 'test.test' has a verification token.",
        );

        $model = new VerifyEmailForm($user->verification_token);

        $user = $model->verifyEmail();

        self::assertInstanceOf(
            User::class,
            $user,
            'Returns a User instance.',
        );

        $user->refresh();

        self::assertSame(
            'test.test',
            $user->username,
            "Verified user has username 'test.test'.",
        );
        self::assertSame(
            'test.test@example.com',
            $user->email,
            "Verified user has email 'test.test@example.com'.",
        );
        self::assertSame(
            User::STATUS_ACTIVE,
            $user->status,
            "Verified user status is 'ACTIVE'.",
        );
        self::assertNull(
            $user->verification_token,
            'Verification token is cleared after verification.',
        );
        self::assertTrue(
            $user->validatePassword('Test1234'),
            "Verified 'user password' still validates correctly.",
        );
    }

    public function testVerifyEmailReturnsNullWhenUserIsNull(): void
    {
        $user = User::findOne(['username' => 'test.test']);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.test' exists.",
        );
        self::assertNotNull(
            $user->verification_token,
            "Fixture user 'test.test' has a verification token.",
        );

        $form = new VerifyEmailForm($user->verification_token);
        $reflection = new ReflectionProperty($form, 'user');

        $reflection->setValue($form, null);

        self::assertNull(
            $form->verifyEmail(),
            "Return 'null' when user is 'null'.",
        );
    }
}
