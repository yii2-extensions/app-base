<?php

declare(strict_types=1);

namespace app\tests\unit\usecases\user;

use app\tests\support\fixtures\UserFixture;
use app\usecases\shared\user\User;
use app\usecases\user\ConfirmEmailAction;
use Codeception\Test\Unit;
use Yii;
use yii\base\{Event, ModelEvent};
use yii\db\BaseActiveRecord;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for {@see ConfirmEmailAction} email verification commit.
 *
 * @copyright Copyright (C) 2026 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ConfirmEmailActionTest extends Unit
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

    public function testActionConfirmEmailFailure(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/confirm-email';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $user = User::findOne(['username' => 'test.fail', 'status' => User::STATUS_INACTIVE]);

        self::assertInstanceOf(
            User::class,
            $user,
            "Fixture user 'test.fail' must exist.",
        );

        $token = $user->verification_token;

        self::assertNotNull(
            $token,
            'Fixture must carry a verification token.',
        );

        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };

        Event::on(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        try {
            $action = new ConfirmEmailAction();
            $response = $action->run($token);
        } finally {
            Event::off(User::class, BaseActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }

        self::assertNotEmpty(
            $response,
            'Failure case must produce a redirect response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('error'),
            'Error flash must be set on save failure.',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('success'),
            'Success flash must be absent on failure.',
        );
    }

    public function testActionConfirmEmailSuccess(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/confirm-email';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

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

        $action = new ConfirmEmailAction();

        $response = $action->run($token);

        self::assertNotEmpty(
            $response,
            'Successful verification must produce a redirect response.',
        );
        self::assertTrue(
            Yii::$app->session->hasFlash('success'),
            '`success` flash must be set on verification.',
        );
        self::assertFalse(
            Yii::$app->session->hasFlash('error'),
            '`error` flash must be absent on success.',
        );
    }

    public function testThrowBadRequestHttpExceptionWhenConfirmEmailTokenIsInvalid(): void
    {
        $_SERVER['REQUEST_URI'] = '/user/confirm-email';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $action = new ConfirmEmailAction();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Wrong verify email token.',
        );

        $action->run('invalid-token');
    }

    protected function tearDown(): void
    {
        Yii::$app->session->removeAllFlashes();

        unset($_SERVER['REQUEST_URI'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }
}
