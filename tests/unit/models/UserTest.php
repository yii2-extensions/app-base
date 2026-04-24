<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\User;
use app\tests\support\fixtures\UserFixture;
use app\tests\support\UnitTester;
use Yii;
use yii\base\NotSupportedException;

use function strlen;

/**
 * Unit tests for {@see \app\models\User} ActiveRecord identity model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class UserTest extends \Codeception\Test\Unit
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

    public function testFindUserById(): void
    {
        $user = User::findIdentity(1);

        self::assertNotEmpty(
            $user,
            "Active user with ID '1' exists.",
        );
        self::assertSame(
            'admin',
            $user?->username,
            "User with ID '1' has username 'admin'.",
        );
        self::assertNull(
            User::findIdentity(999),
            "User with non-existing ID '999' returns 'null'.",
        );
    }

    public function testFindUserByPasswordResetToken(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );
        self::assertNotNull(
            $user->password_reset_token,
            "Fixture user 'okirlin' has a password reset token.",
        );

        $token = $user->password_reset_token;

        $foundUser = User::findByPasswordResetToken($token);

        self::assertNotEmpty(
            $foundUser,
            "User is found by a valid 'password reset token'.",
        );
        self::assertSame(
            'okirlin',
            $foundUser?->username,
            "Password reset token resolves to user 'okirlin'.",
        );
        self::assertNull(
            User::findByPasswordResetToken('notexistingtoken_1391882543'),
            "An invalid 'password reset token' returns 'null'.",
        );
    }

    public function testFindUserByUsername(): void
    {
        self::assertNotEmpty(
            User::findByUsername('okirlin'),
            "Active user 'okirlin' is found by username.",
        );
        self::assertNull(
            User::findByUsername('not-existing'),
            "Non-existing username returns 'null'.",
        );
    }

    public function testFindUserByVerificationToken(): void
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

        $foundUser = User::findByVerificationToken($user->verification_token);

        self::assertNotEmpty(
            $foundUser,
            'Inactive user is found by verification token.',
        );
        self::assertSame(
            'test.test',
            $foundUser?->username,
            "Verification token resolves to user 'test.test'.",
        );
        self::assertNull(
            User::findByVerificationToken('non_existing_token'),
            "A non-existing verification token returns 'null'.",
        );
    }

    public function testGenerateAuthKey(): void
    {
        $user = new User();

        $user->generateAuthKey();

        self::assertNotEmpty(
            $user->auth_key,
            'Auth key is generated.',
        );
        self::assertSame(
            32,
            strlen($user->auth_key),
            "Auth key length is '32' characters.",
        );
    }

    public function testGenerateEmailVerificationToken(): void
    {
        $user = new User();

        $user->generateEmailVerificationToken();

        self::assertNotEmpty(
            $user->verification_token,
            'Email verification token is generated.',
        );
        self::assertTrue(
            User::isVerificationTokenValid($user->verification_token),
            'Newly generated verification token is valid.',
        );
    }

    public function testGeneratePasswordResetToken(): void
    {
        $user = new User();

        $user->generatePasswordResetToken();

        self::assertNotEmpty(
            $user->password_reset_token,
            'Password reset token is generated.',
        );
        self::assertTrue(
            User::isPasswordResetTokenValid($user->password_reset_token),
            'Newly generated password reset token is valid.',
        );
    }

    public function testIsPasswordResetTokenValidWithExpiredToken(): void
    {
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];

        $expiredToken = 'somevalidvalue_' . (time() - $expire - 1);

        self::assertFalse(
            User::isPasswordResetTokenValid($expiredToken),
            'An expired password reset token is invalid.',
        );
    }

    public function testIsPasswordResetTokenValidWithMalformedTimestamp(): void
    {
        self::assertFalse(
            User::isPasswordResetTokenValid('token_123abc'),
            'Token with non-digit timestamp suffix is invalid.',
        );
        self::assertFalse(
            User::isPasswordResetTokenValid('token_'),
            'Token with empty timestamp suffix is invalid.',
        );
    }

    public function testIsPasswordResetTokenValidWithNullToken(): void
    {
        self::assertFalse(
            User::isPasswordResetTokenValid(null),
            "'null' token is invalid.",
        );
        self::assertFalse(
            User::isPasswordResetTokenValid(''),
            'Empty token is invalid.',
        );
    }

    public function testIsPasswordResetTokenValidWithoutUnderscore(): void
    {
        self::assertFalse(
            User::isPasswordResetTokenValid('tokenWithoutUnderscore'),
            'Token without underscore separator is invalid.',
        );
    }

    public function testIsVerificationTokenValidWithExpiredToken(): void
    {
        $expire = Yii::$app->params['user.emailVerificationTokenExpire'];

        $expiredToken = 'somevalidvalue_' . (time() - $expire - 1);

        self::assertFalse(
            User::isVerificationTokenValid($expiredToken),
            'Expired verification token is invalid.',
        );
    }

    public function testIsVerificationTokenValidWithMalformedTimestamp(): void
    {
        self::assertFalse(
            User::isVerificationTokenValid('token_123abc'),
            'Verification token with non-digit timestamp suffix is invalid.',
        );
        self::assertFalse(
            User::isVerificationTokenValid('token_'),
            'Verification token with empty timestamp suffix is invalid.',
        );
    }

    public function testIsVerificationTokenValidWithNullToken(): void
    {
        self::assertFalse(
            User::isVerificationTokenValid(null),
            "'null' verification token is invalid.",
        );
        self::assertFalse(
            User::isVerificationTokenValid(''),
            'Empty verification token is invalid.',
        );
    }

    public function testIsVerificationTokenValidWithoutUnderscore(): void
    {
        self::assertFalse(
            User::isVerificationTokenValid('tokenWithoutUnderscore'),
            'Verification token without underscore separator is invalid.',
        );
    }

    public function testRemovePasswordResetToken(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        $user->removePasswordResetToken();

        self::assertEmpty(
            $user->password_reset_token,
            'Password reset token is removed.',
        );
    }

    public function testSetPassword(): void
    {
        $user = new User();

        $user->setPassword('new_password');

        self::assertNotEmpty(
            $user->password_hash,
            'Password hash is generated after set password.',
        );
        self::assertTrue(
            $user->validatePassword('new_password'),
            'Newly set password validates correctly.',
        );
    }

    public function testThrowNotSupportedExceptionWhenFindIdentityByAccessToken(): void
    {
        self::assertInstanceOf(
            UnitTester::class,
            $this->tester,
            'Tester instance is available.',
        );

        $this->tester->expectThrowable(
            NotSupportedException::class,
            static function (): void {
                User::findIdentityByAccessToken('any-token');
            },
        );
    }

    public function testValidateAuthKey(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );

        self::assertTrue(
            $user->validateAuthKey($user->auth_key),
            'Correct auth key validates successfully.',
        );
        self::assertFalse(
            $user->validateAuthKey('wrong-auth-key'),
            'Wrong auth key does not validate.',
        );
    }

    public function testValidatePassword(): void
    {
        $user = User::findByUsername('okirlin');

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'okirlin' exists.",
        );
        self::assertTrue(
            $user->validatePassword('password_0'),
            'Correct password validates successfully.',
        );
        self::assertFalse(
            $user->validatePassword('wrong_password'),
            'A wrong password does not validate.',
        );
    }
}
