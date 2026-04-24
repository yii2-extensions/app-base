<?php

declare(strict_types=1);

namespace app\tests\unit\migrations;

use app\migrations\M260330000000CreateUserTable;
use Yii;

/**
 * Unit tests for {@see \app\migrations\M260330000000CreateUserTable} migration.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class CreateUserTableTest extends \Codeception\Test\Unit
{
    public function testSafeDownDropsUserTable(): void
    {
        $db = Yii::$app->db;

        $schema = $db->schema;

        // table exists before migration down.
        $schema->refresh();

        verify($schema->getTableSchema('{{%user}}'))
            ->notNull("User table exists before 'safeDown'.");

        $migration = new M260330000000CreateUserTable(['db' => $db]);

        $migration->down();
        $schema->refresh();

        verify($schema->getTableSchema('{{%user}}'))
            ->null("User table is dropped after 'safeDown'.");

        // recreate the table for subsequent tests.
        $migration->up();
        $schema->refresh();

        verify($schema->getTableSchema('{{%user}}'))
            ->notNull("User table is recreated after 'safeUp'.");
    }

    public function testSafeUpCreatesUserTable(): void
    {
        $db = Yii::$app->db;

        $schema = $db->schema;

        $migration = new M260330000000CreateUserTable(['db' => $db]);

        // drop and recreate the table so the assertions actually exercise 'safeUp()'.
        $migration->down();
        $schema->refresh();

        verify($schema->getTableSchema('{{%user}}'))
            ->null("User table is dropped before re-running 'safeUp'.");

        $migration->up();
        $schema->refresh();
        $tableSchema = $schema->getTableSchema('{{%user}}');

        self::assertNotNull(
            $tableSchema,
            "User table exists after 'safeUp'.",
        );

        $columns = $tableSchema->columns;

        $expectedColumns = [
            'id',
            'username',
            'auth_key',
            'password_hash',
            'password_reset_token',
            'email',
            'status',
            'created_at',
            'updated_at',
            'verification_token',
        ];

        foreach ($expectedColumns as $column) {
            self::assertArrayHasKey(
                $column,
                $columns,
                "Column '$column' must exist on the 'user' table.",
            );
        }

        // verify primary key.
        self::assertSame(
            ['id'],
            $tableSchema->primaryKey,
            "Primary key is 'id'.",
        );
        // verify NOT NULL constraints.
        self::assertFalse(
            $columns['username']->allowNull ?? true,
            "User name is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['auth_key']->allowNull ?? true,
            "Auth key is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['password_hash']->allowNull ?? true,
            "Password hash is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['email']->allowNull ?? true,
            "Email is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['status']->allowNull ?? true,
            "Status is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['created_at']->allowNull ?? true,
            "Created at is 'NOT NULL'.",
        );
        self::assertFalse(
            $columns['updated_at']->allowNull ?? true,
            "Updated at is 'NOT NULL'.",
        );
        // verify nullable token columns.
        self::assertTrue(
            $columns['password_reset_token']->allowNull ?? false,
            "Password reset token is 'nullable'.",
        );
        self::assertTrue(
            $columns['verification_token']->allowNull ?? false,
            "Verification token is 'nullable'.",
        );
        // verify status defaults to inactive (`9`).
        self::assertEquals(
            9,
            $columns['status']->defaultValue ?? null,
            "Status defaults to '9' ('inactive').",
        );
    }
}
