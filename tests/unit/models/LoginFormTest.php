<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\LoginForm;
use app\tests\support\fixtures\UserFixture;
use Yii;
use yii\log\Logger;

use function is_array;
use function is_string;

/**
 * Unit tests for {@see \app\models\LoginForm} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class LoginFormTest extends \Codeception\Test\Unit
{
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

    public function testGetUserQueriesDatabaseOnlyOnceWhenUserDoesNotExist(): void
    {
        $model = new LoginForm(
            [
                'username' => 'not_existing_username',
                'password' => 'irrelevant',
            ],
        );

        $originalLogger = Yii::getLogger();

        $realLogger = new Logger();

        Yii::setLogger($realLogger);

        try {
            self::assertNull(
                $model->getUser(),
                "Return 'null' on the first call for a non-existent 'username'.",
            );

            $countAfterFirst = $this->countDbQueries($realLogger);

            self::assertGreaterThan(
                0,
                $countAfterFirst,
                "A count of '0' would pass the later equality check as a 'false' positive.",
            );

            self::assertNull(
                $model->getUser(),
                "Return 'null' on the second call for a non-existent 'username'.",
            );

            $countAfterSecond = $this->countDbQueries($realLogger);

            self::assertSame(
                $countAfterFirst,
                $countAfterSecond,
                'No additional database queries are made on the second call.',
            );
        } finally {
            Yii::setLogger($originalLogger);
        }
    }

    public function testLoginCorrect(): void
    {
        $model = new LoginForm(
            [
                'username' => 'okirlin',
                'password' => 'password_0',
            ],
        );

        self::assertTrue(
            $model->login(),
            'Login succeeds with correct credentials.',
        );
        self::assertFalse(
            Yii::$app->user->isGuest,
            'User is no longer a guest after login.',
        );
        self::assertArrayNotHasKey(
            'password',
            $model->errors,
            'Password error does not exist after successful login.',
        );
    }

    public function testLoginDeletedAccount(): void
    {
        $model = new LoginForm(
            [
                'username' => 'troy.becker',
                'password' => 'password_0',
            ],
        );

        self::assertFalse(
            $model->login(),
            'Login fails for a deleted account.',
        );
        self::assertTrue(
            Yii::$app->user->isGuest,
            "User remains a 'guest' after deleted account login attempt.",
        );
    }

    public function testLoginInactiveAccount(): void
    {
        $model = new LoginForm(
            [
                'username' => 'test.test',
                'password' => 'Test1234',
            ],
        );

        self::assertFalse(
            $model->login(),
            'Login fails for an inactive account.',
        );
        self::assertTrue(
            Yii::$app->user->isGuest,
            "User remains a 'guest' after inactive account login attempt.",
        );
    }

    public function testLoginNoUser(): void
    {
        $model = new LoginForm(
            [
                'username' => 'not_existing_username',
                'password' => 'not_existing_password',
            ],
        );

        self::assertFalse(
            $model->login(),
            'Login fails with non-existing username.',
        );
        self::assertTrue(
            Yii::$app->user->isGuest,
            "User remains a 'guest' after failed login.",
        );
    }

    public function testLoginReturnsFalseWhenUserIsNull(): void
    {
        $model = $this->make(
            LoginForm::class,
            [
                'validate' => true,
                'getUser' => null,
            ],
        );

        self::assertFalse(
            $model->login(),
            "Login returns 'false' when user is 'null' after validation.",
        );
    }

    public function testLoginWrongPassword(): void
    {
        $model = new LoginForm(
            [
                'username' => 'okirlin',
                'password' => 'wrong_password',
            ],
        );

        self::assertFalse(
            $model->login(),
            'Login fails with wrong password.',
        );
        self::assertTrue(
            Yii::$app->user->isGuest,
            "User remains a 'guest' after wrong password.",
        );
        self::assertArrayHasKey(
            'password',
            $model->errors,
            'A password validation error is present.',
        );
    }

    protected function _after(): void
    {
        Yii::$app->user->logout();
    }

    private function countDbQueries(Logger $logger): int
    {
        $count = 0;

        foreach ($logger->messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $category = $message[2] ?? null;

            if (is_string($category) && str_starts_with($category, 'yii\db\Command::query')) {
                $count++;
            }
        }

        return $count;
    }
}
