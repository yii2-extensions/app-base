<?php

declare(strict_types=1);

namespace app\tests\unit\migrations;

use app\migrations\M260403000000CreateAdminUser;
use app\usecases\shared\user\User;
use Codeception\Test\Unit;
use Yii;

/**
 * Unit tests for {@see M260403000000CreateAdminUser} migration.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class CreateAdminUserTest extends Unit
{
    public function testSafeDownDeletesAdminUser(): void
    {
        $db = Yii::$app->db;

        $migration = new M260403000000CreateAdminUser(['db' => $db]);

        $expectedUsername = Yii::$app->params['admin.username'];

        $db->createCommand()->delete('{{%user}}', ['username' => $expectedUsername])->execute();
        $migration->up();

        $admin = User::find()->where(['username' => $expectedUsername])->one();

        verify($admin)
            ->notNull("Admin user exists after 'safeUp'.");

        $migration->down();
        $admin = User::find()->where(['username' => $expectedUsername])->one();

        verify($admin)
            ->null("Admin user is deleted after 'safeDown'.");
    }

    public function testSafeUpCreatesAdminUser(): void
    {
        $db = Yii::$app->db;

        $expectedUsername = Yii::$app->params['admin.username'];
        $expectedEmail = Yii::$app->params['admin.email'];

        // clean up if admin already exists from fixtures.
        $db->createCommand()->delete('{{%user}}', ['username' => $expectedUsername])->execute();

        $migration = new M260403000000CreateAdminUser(['db' => $db]);

        $migration->up();

        $admin = User::find()->where(['username' => $expectedUsername])->one();

        self::assertInstanceOf(
            User::class,
            $admin,
            'Admin user exists.',
        );

        verify($admin->username)
            ->equals($expectedUsername);
        verify($admin->email)
            ->equals($expectedEmail);
        verify($admin->status)
            ->equals(User::STATUS_ACTIVE, "Status is 'active'.");

        $expectedPassword = Yii::$app->params['admin.password'];

        verify(Yii::$app->security->validatePassword($expectedPassword, $admin->password_hash))
            ->true('Admin password matches configured value.');

        // clean up for other tests.
        $migration->down();
    }
}
